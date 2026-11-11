<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Calibration;
use App\Models\Department;
use App\Services\AttachmentService;
use App\Services\ComplianceService;
use Illuminate\Http\Request;

class CalibrationController extends Controller
{
    public function __construct(protected ComplianceService $service)
    {
    }

    public function index(Request $request)
    {
        $calibrations = Calibration::with(['asset', 'department'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('instrument_name', 'like', "%$v%")->orWhere('instrument_code', 'like', "%$v%")->orWhere('serial_number', 'like', "%$v%")))
            ->orderBy('next_due_date')->paginate(20)->withQueryString();

        return view('calibrations.index', ['calibrations' => $calibrations, 'departments' => Department::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('calibrations.form', ['calibration' => new Calibration(['frequency_days' => 365])] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        if (empty($data['next_due_date']) && !empty($data['last_calibration_date'])) {
            $data['next_due_date'] = \Carbon\Carbon::parse($data['last_calibration_date'])->addDays((int) $data['frequency_days'])->toDateString();
        }
        $calibration = Calibration::create($data);
        $calibration->refreshStatus((int) setting('pm.due_soon_days', 30));
        $calibration->save();

        if ($calibration->last_calibration_date) {
            $record = $calibration->records()->create(['calibration_date' => $calibration->last_calibration_date, 'next_due_date' => $calibration->next_due_date, 'certificate_no' => $calibration->certificate_no, 'agency' => $calibration->agency, 'result' => $calibration->result, 'recorded_by' => auth()->id()]);
            AttachmentService::storeMany($record, $request->file('attachments'), 'certificate');
        }
        $calibration->logHistory('created', 'Instrument registered');

        flash_toast('success', 'Instrument ' . $calibration->instrument_code . ' registered.');
        return redirect()->route('calibrations.show', $calibration);
    }

    public function show(Calibration $calibration)
    {
        $calibration->load(['asset', 'department', 'records.recorder', 'records.attachments', 'history.user']);
        return view('calibrations.show', compact('calibration'));
    }

    public function edit(Calibration $calibration)
    {
        return view('calibrations.form', compact('calibration') + $this->formData());
    }

    public function update(Request $request, Calibration $calibration)
    {
        $calibration->update($this->validated($request, $calibration));
        $calibration->refreshStatus((int) setting('pm.due_soon_days', 30));
        $calibration->save();
        $calibration->logHistory('updated', 'Instrument details updated');
        flash_toast('success', 'Instrument updated.');
        return redirect()->route('calibrations.show', $calibration);
    }

    public function storeRecord(Request $request, Calibration $calibration)
    {
        $data = $request->validate([
            'calibration_date' => 'required|date',
            'next_due_date'    => 'nullable|date|after:calibration_date',
            'certificate_no'   => 'nullable|string|max:100',
            'agency'           => 'nullable|string|max:150',
            'result'           => 'required|in:passed,failed',
            'remarks'          => 'nullable|string|max:2000',
            'attachments.*'    => 'nullable|' . AttachmentService::rule(),
        ]);
        $this->service->recordCalibration($calibration, $data, $request->file('attachments'));
        flash_toast('success', 'Calibration recorded. Next due ' . show_date($calibration->fresh()->next_due_date) . '.');
        return back();
    }

    /** Mark "under calibration" (sent to lab) or back to valid. */
    public function updateStatus(Request $request, Calibration $calibration)
    {
        $data = $request->validate(['status' => 'required|in:under_calibration,valid']);
        $from = $calibration->status;
        $calibration->status = $data['status'];
        if ($data['status'] === 'valid') {
            $calibration->refreshStatus((int) setting('pm.due_soon_days', 30));
        }
        $calibration->save();
        $calibration->logHistory('status_changed', null, $from, $calibration->status);
        flash_toast('success', 'Status updated.');
        return back();
    }

    protected function validated(Request $request, ?Calibration $calibration = null): array
    {
        return $request->validate([
            'instrument_code'       => 'required|string|max:50|unique:pm_calibrations,instrument_code,' . ($calibration?->id ?? 'NULL'),
            'instrument_name'       => 'required|string|max:150',
            'asset_id'              => 'nullable|exists:pm_assets,id',
            'department_id'         => 'nullable|exists:pm_departments,id',
            'serial_number'         => 'nullable|string|max:100',
            'agency'                => 'nullable|string|max:150',
            'frequency_days'        => 'required|integer|min:1',
            'last_calibration_date' => 'nullable|date',
            'next_due_date'         => 'nullable|date',
            'certificate_no'        => 'nullable|string|max:100',
            'result'                => 'nullable|in:passed,failed',
            'remarks'               => 'nullable|string|max:2000',
            'attachments.*'         => 'nullable|' . AttachmentService::rule(),
        ]);
    }

    protected function formData(): array
    {
        return ['assets' => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']), 'departments' => Department::orderBy('name')->get()];
    }
}
