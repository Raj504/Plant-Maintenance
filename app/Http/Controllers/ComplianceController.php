<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Compliance;
use App\Models\Department;
use App\Services\AttachmentService;
use App\Services\ComplianceService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function __construct(protected ComplianceService $service)
    {
    }

    public function index(Request $request)
    {
        $compliances = Compliance::with(['asset', 'department'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->due, fn ($q) => $q->whereIn('status', ['due_soon', 'overdue']))
            ->when($request->compliance_type, fn ($q, $v) => $q->where('compliance_type', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('inspection_name', 'like', "%$v%")->orWhere('compliance_no', 'like', "%$v%")->orWhere('certificate_no', 'like', "%$v%")))
            ->orderBy('next_due_date')->paginate(20)->withQueryString();

        return view('compliances.index', ['compliances' => $compliances, 'types' => Category::ofType('compliance')->get(), 'departments' => Department::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('compliances.form', ['compliance' => new Compliance(['frequency_days' => 365])] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['compliance_no'] = DocumentNumberService::next('compliance');
        $data['created_by']    = auth()->id();
        if (empty($data['next_due_date']) && !empty($data['last_inspection_date']) && !empty($data['frequency_days'])) {
            $data['next_due_date'] = \Carbon\Carbon::parse($data['last_inspection_date'])->addDays((int) $data['frequency_days'])->toDateString();
        }

        $compliance = Compliance::create($data);
        $compliance->refreshStatus((int) setting('pm.due_soon_days', 30));
        $compliance->save();

        // First record = the last inspection, so history starts complete
        if ($compliance->last_inspection_date) {
            $record = $compliance->records()->create(['inspection_date' => $compliance->last_inspection_date, 'next_due_date' => $compliance->next_due_date, 'certificate_no' => $compliance->certificate_no, 'agency' => $compliance->agency, 'result' => $compliance->result, 'recorded_by' => auth()->id()]);
            AttachmentService::storeMany($record, $request->file('attachments'), 'certificate');
        } else {
            AttachmentService::storeMany($compliance, $request->file('attachments'), 'document');
        }
        $compliance->logHistory('created', 'Compliance record created');

        flash_toast('success', 'Compliance ' . $compliance->compliance_no . ' created.');
        return redirect()->route('compliances.show', $compliance);
    }

    public function show(Compliance $compliance)
    {
        $compliance->load(['asset', 'department', 'records.recorder', 'records.attachments', 'attachments', 'history.user']);
        return view('compliances.show', compact('compliance'));
    }

    public function edit(Compliance $compliance)
    {
        return view('compliances.form', compact('compliance') + $this->formData());
    }

    public function update(Request $request, Compliance $compliance)
    {
        $compliance->update($this->validated($request));
        $compliance->refreshStatus((int) setting('pm.due_soon_days', 30));
        $compliance->save();
        $compliance->logHistory('updated', 'Compliance details updated');
        flash_toast('success', 'Compliance updated.');
        return redirect()->route('compliances.show', $compliance);
    }

    /** Record a new inspection: history row + certificate upload + due date rolls forward. */
    public function storeRecord(Request $request, Compliance $compliance)
    {
        $data = $request->validate([
            'inspection_date' => 'required|date',
            'next_due_date'   => 'nullable|date|after:inspection_date',
            'certificate_no'  => 'nullable|string|max:100',
            'agency'          => 'nullable|string|max:150',
            'result'          => 'required|in:passed,failed,conditional',
            'remarks'         => 'nullable|string|max:2000',
            'attachments.*'   => 'nullable|' . AttachmentService::rule(),
        ]);
        $this->service->recordInspection($compliance, $data, $request->file('attachments'));
        flash_toast('success', 'Inspection recorded. Next due ' . show_date($compliance->fresh()->next_due_date) . '.');
        return back();
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'asset_id'             => 'nullable|exists:pm_assets,id',
            'compliance_type'      => 'required|string|max:100',
            'inspection_name'      => 'required|string|max:150',
            'authority'            => 'nullable|string|max:150',
            'agency'               => 'nullable|string|max:150',
            'department_id'        => 'nullable|exists:pm_departments,id',
            'frequency_days'       => 'nullable|integer|min:1',
            'last_inspection_date' => 'nullable|date',
            'next_due_date'        => 'nullable|date',
            'certificate_no'       => 'nullable|string|max:100',
            'result'               => 'nullable|in:passed,failed,conditional',
            'remarks'              => 'nullable|string|max:2000',
            'attachments.*'        => 'nullable|' . AttachmentService::rule(),
        ]);
    }

    protected function formData(): array
    {
        return ['assets' => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']), 'types' => Category::ofType('compliance')->get(), 'departments' => Department::orderBy('name')->get()];
    }
}
