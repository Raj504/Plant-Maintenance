<?php

namespace App\Http\Controllers;

use App\Models\AmcContract;
use App\Models\AmcVisit;
use App\Models\Asset;
use App\Models\Vendor;
use App\Models\VendorService;
use App\Services\AttachmentService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;

class AmcController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) setting('pm.amc_expiry_alert_days', 30);
        $contracts = AmcContract::with(['vendor', 'assets'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->vendor_id, fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($request->expiring, fn ($q) => $q->where('status', 'active')->whereBetween('end_date', [today(), today()->addDays($days)]))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('amc_no', 'like', "%$v%")->orWhere('name', 'like', "%$v%")))
            ->orderBy('end_date')->paginate(20)->withQueryString();

        return view('amc.index', ['contracts' => $contracts, 'vendors' => Vendor::orderBy('name')->get(), 'days' => $days]);
    }

    public function create()
    {
        return view('amc.form', ['amc' => new AmcContract(['start_date' => today(), 'end_date' => today()->addYear(), 'status' => 'active', 'service_frequency' => 'quarterly'])] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['amc_no']     = DocumentNumberService::next('amc');
        $data['created_by'] = auth()->id();
        $assets = $data['asset_ids'] ?? [];
        unset($data['asset_ids']);

        $amc = AmcContract::create($data);
        $amc->assets()->sync($assets);
        AttachmentService::storeMany($amc, $request->file('attachments'), 'contract');
        $amc->logHistory('created', 'Contract created');

        $this->generateVisits($amc);

        flash_toast('success', 'AMC ' . $amc->amc_no . ' created with scheduled visits.');
        return redirect()->route('amc.show', $amc);
    }

    public function show(AmcContract $amc)
    {
        $amc->load(['vendor', 'assets', 'visits', 'services.workOrder', 'attachments.uploader', 'history.user']);
        return view('amc.show', compact('amc'));
    }

    public function edit(AmcContract $amc)
    {
        $amc->load('assets');
        return view('amc.form', ['amc' => $amc] + $this->formData());
    }

    public function update(Request $request, AmcContract $amc)
    {
        $data = $this->validated($request);
        $assets = $data['asset_ids'] ?? [];
        unset($data['asset_ids']);
        $amc->update($data);
        $amc->assets()->sync($assets);
        AttachmentService::storeMany($amc, $request->file('attachments'), 'contract');
        $amc->logHistory('updated', 'Contract updated');
        flash_toast('success', 'AMC updated.');
        return redirect()->route('amc.show', $amc);
    }

    public function storeVisit(Request $request, AmcContract $amc)
    {
        $data = $request->validate(['scheduled_date' => 'required|date', 'remarks' => 'nullable|string|max:500']);
        $amc->visits()->create($data + ['status' => 'scheduled']);
        flash_toast('success', 'Visit scheduled.');
        return back();
    }

    public function completeVisit(Request $request, AmcContract $amc, AmcVisit $visit)
    {
        abort_unless($visit->amc_id === $amc->id, 404);
        $data = $request->validate(['actual_date' => 'required|date', 'work_done' => 'nullable|string|max:2000', 'status' => 'required|in:completed,missed']);
        $visit->update($data);
        $amc->logHistory('visit_' . $data['status'], 'Visit scheduled ' . show_date($visit->scheduled_date) . ' marked ' . $data['status']);
        flash_toast('success', 'Visit updated.');
        return back();
    }

    public function storeService(Request $request, AmcContract $amc)
    {
        $data = $request->validate([
            'service_date'          => 'required|date',
            'work_order_id'         => 'nullable|exists:pm_work_orders,id',
            'response_hours'        => 'nullable|numeric|min:0',
            'resolution_hours'      => 'nullable|numeric|min:0',
            'work_quality'          => 'required|integer|min:1|max:5',
            'cost'                  => 'nullable|numeric|min:0',
            'feedback'              => 'nullable|string|max:1000',
            'attended'              => 'boolean',
            'repeat_failure'        => 'boolean',
        ]);
        // SLA compliance is computed from the contract terms
        $data['response_within_sla']   = !$amc->response_time_hours || ($data['response_hours'] ?? 0) <= $amc->response_time_hours;
        $data['resolution_within_sla'] = !$amc->resolution_time_hours || ($data['resolution_hours'] ?? 0) <= $amc->resolution_time_hours;
        $data['attended']       = $request->boolean('attended', true);
        $data['repeat_failure'] = $request->boolean('repeat_failure');

        VendorService::create($data + ['vendor_id' => $amc->vendor_id, 'amc_id' => $amc->id, 'recorded_by' => auth()->id()]);
        flash_toast('success', 'Vendor service recorded.');
        return back();
    }

    // ------------------------------------------------------------ Helpers

    /** Create scheduled visits for the contract period according to service frequency. */
    protected function generateVisits(AmcContract $amc): void
    {
        $months = ['monthly' => 1, 'quarterly' => 3, 'half_yearly' => 6, 'yearly' => 12][$amc->service_frequency] ?? null;
        if (!$months) {
            return;
        }
        $date = $amc->start_date->copy()->addMonths($months);
        while ($date->lte($amc->end_date)) {
            $amc->visits()->create(['scheduled_date' => $date->toDateString(), 'status' => 'scheduled']);
            $date->addMonths($months);
        }
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'vendor_id'             => 'required|exists:pm_vendors,id',
            'name'                  => 'required|string|max:150',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date|after:start_date',
            'contract_value'        => 'nullable|numeric|min:0',
            'payment_terms'         => 'nullable|string|max:255',
            'service_frequency'     => 'nullable|in:monthly,quarterly,half_yearly,yearly,on_call',
            'scope_of_work'         => 'nullable|string|max:3000',
            'response_time_hours'   => 'nullable|integer|min:0',
            'resolution_time_hours' => 'nullable|integer|min:0',
            'service_conditions'    => 'nullable|string|max:3000',
            'contact_person'        => 'nullable|string|max:100',
            'contact_number'        => 'nullable|string|max:30',
            'status'                => 'required|in:active,expired,terminated',
            'remarks'               => 'nullable|string|max:2000',
            'asset_ids'             => 'nullable|array',
            'asset_ids.*'           => 'exists:pm_assets,id',
            'attachments.*'         => 'nullable|' . AttachmentService::rule(),
        ]);
    }

    protected function formData(): array
    {
        return ['vendors' => Vendor::where('is_active', true)->orderBy('name')->get(), 'assets' => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name'])];
    }
}
