<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\LotoRecord;
use App\Models\SafetyPermit;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AttachmentService;
use App\Services\DocumentNumberService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Permit to Work (PTW) + Lockout/Tagout (LOTO).
 * draft -> issued (submitted for approval) -> active (approved) -> closed
 * A permit cannot close while any LOTO isolation is still in place.
 */
class SafetyPermitController extends Controller
{
    public function index(Request $request)
    {
        $permits = SafetyPermit::with(['asset', 'workOrder', 'issuer', 'approver'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->permit_type, fn ($q, $v) => $q->where('permit_type', $v))
            ->when($request->q, fn ($q, $v) => $q->where('permit_no', 'like', "%$v%"))
            ->latest()->paginate(20)->withQueryString();

        return view('permits.index', compact('permits'));
    }

    public function create(Request $request)
    {
        $wo = $request->work_order_id ? WorkOrder::find($request->work_order_id) : null;
        $permit = new SafetyPermit([
            'work_order_id'    => $wo?->id,
            'asset_id'         => $wo?->asset_id,
            'work_description' => $wo?->description,
            'work_location'    => $wo?->asset?->location,
            'permit_type'      => 'general',
            'valid_from'       => now(),
            'valid_until'      => now()->addHours(8),
        ]);
        return view('permits.form', ['permit' => $permit] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['permit_no']  = DocumentNumberService::next('permit');
        $data['issued_by']  = auth()->id();
        $data['status']     = 'draft';

        $permit = SafetyPermit::create($data);
        AttachmentService::storeMany($permit, $request->file('attachments'), 'permit');
        $permit->logHistory('created', 'Permit drafted', null, 'draft');

        flash_toast('success', 'Permit ' . $permit->permit_no . ' drafted. Add LOTO records and submit for approval.');
        return redirect()->route('permits.show', $permit);
    }

    public function show(SafetyPermit $permit)
    {
        $permit->load(['asset', 'workOrder', 'issuer', 'approver', 'closer', 'lotoRecords.isolator', 'lotoRecords.verifier', 'lotoRecords.deisolator', 'attachments.uploader', 'history.user']);
        return view('permits.show', ['permit' => $permit, 'users' => User::where('is_active', true)->orderBy('name')->get()]);
    }

    public function print(SafetyPermit $permit)
    {
        $permit->load(['asset', 'workOrder', 'issuer', 'approver', 'closer', 'lotoRecords.isolator', 'lotoRecords.verifier', 'lotoRecords.deisolator']);
        return view('permits.print', compact('permit'));
    }

    public function edit(SafetyPermit $permit)
    {
        abort_unless($permit->status === 'draft', 403, 'Only draft permits can be edited.');
        return view('permits.form', ['permit' => $permit] + $this->formData());
    }

    public function update(Request $request, SafetyPermit $permit)
    {
        abort_unless($permit->status === 'draft', 403, 'Only draft permits can be edited.');
        $permit->update($this->validated($request));
        AttachmentService::storeMany($permit, $request->file('attachments'), 'permit');
        $permit->logHistory('updated', 'Permit updated');
        flash_toast('success', 'Permit updated.');
        return redirect()->route('permits.show', $permit);
    }

    // ------------------------------------------------------------ Lifecycle

    public function issue(SafetyPermit $permit)
    {
        abort_unless($permit->status === 'draft', 403);
        $permit->update(['status' => 'issued']);
        $permit->logHistory('issued', 'Submitted for safety officer approval', 'draft', 'issued');
        NotificationService::notifyPermission('pm.safety.approve', 'permit_approval', 'Permit ' . $permit->permit_no . ' awaiting approval', $permit->asset->name, route('permits.show', $permit), 'warning');
        flash_toast('success', 'Permit submitted for approval.');
        return back();
    }

    public function approve(SafetyPermit $permit)
    {
        abort_unless($permit->status === 'issued', 403);
        $permit->update(['status' => 'active', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $permit->logHistory('approved', 'Permit approved and active', 'issued', 'active');
        NotificationService::notifyUser($permit->issued_by, 'permit_approved', 'Permit ' . $permit->permit_no . ' approved', 'Work may proceed within the validity window.', route('permits.show', $permit), 'success');
        if ($permit->workOrder?->assigned_to) {
            NotificationService::notifyUser($permit->workOrder->assigned_to, 'permit_approved', 'Permit ' . $permit->permit_no . ' approved for ' . $permit->workOrder->wo_no, null, route('work-orders.show', $permit->workOrder), 'success');
        }
        flash_toast('success', 'Permit approved. It is now active.');
        return back();
    }

    public function close(Request $request, SafetyPermit $permit)
    {
        abort_unless(in_array($permit->status, ['active', 'issued']), 403);
        if ($permit->hasPendingIsolation()) {
            flash_toast('error', 'Cannot close: one or more energy isolations have not been de-isolated and cleared.');
            return back();
        }
        $data = $request->validate(['closure_remarks' => 'required|string|min:3|max:2000']);
        $permit->update(['status' => 'closed', 'closure_remarks' => $data['closure_remarks'], 'closed_by' => auth()->id(), 'closed_at' => now()]);
        $permit->logHistory('closed', $data['closure_remarks'], 'active', 'closed');
        flash_toast('success', 'Permit closed.');
        return back();
    }

    public function cancel(Request $request, SafetyPermit $permit)
    {
        abort_if(in_array($permit->status, ['closed', 'cancelled']), 403);
        $data = $request->validate(['closure_remarks' => 'required|string|min:3|max:2000']);
        $permit->update(['status' => 'cancelled', 'closure_remarks' => $data['closure_remarks'], 'closed_by' => auth()->id(), 'closed_at' => now()]);
        $permit->logHistory('cancelled', $data['closure_remarks'], null, 'cancelled');
        flash_toast('success', 'Permit cancelled.');
        return back();
    }

    // ------------------------------------------------------------ LOTO

    public function storeLoto(Request $request, SafetyPermit $permit)
    {
        abort_if(in_array($permit->status, ['closed', 'cancelled']), 403);
        $data = $request->validate([
            'energy_source'   => 'required|string|max:100',
            'isolation_point' => 'required|string|max:150',
            'lock_number'     => 'nullable|string|max:50',
            'tag_number'      => 'nullable|string|max:50',
            'isolated_at'     => 'required|date',
            'isolated_by'     => 'required|exists:users,id',
            'verified_by'     => 'nullable|exists:users,id',
            'remarks'         => 'nullable|string|max:255',
        ]);
        $permit->lotoRecords()->create($data);
        $permit->logHistory('loto_isolated', $data['energy_source'] . ' isolated at ' . $data['isolation_point'] . ' (lock ' . ($data['lock_number'] ?? '-') . ')');
        flash_toast('success', 'Isolation recorded.');
        return back();
    }

    public function deisolate(Request $request, SafetyPermit $permit, LotoRecord $loto)
    {
        abort_unless($loto->permit_id === $permit->id, 404);
        $data = $request->validate(['deisolated_at' => 'required|date', 'deisolated_by' => 'required|exists:users,id']);
        $loto->update($data);
        $permit->logHistory('loto_deisolated', $loto->energy_source . ' de-isolated at ' . $loto->isolation_point);
        flash_toast('success', 'De-isolation recorded.');
        return back();
    }

    // ------------------------------------------------------------ Helpers

    protected function validated(Request $request): array
    {
        return $request->validate([
            'work_order_id'      => 'nullable|exists:pm_work_orders,id',
            'asset_id'           => 'required|exists:pm_assets,id',
            'work_location'      => 'nullable|string|max:150',
            'work_description'   => 'required|string|max:3000',
            'permit_type'        => ['required', Rule::in(array_keys(SafetyPermit::TYPES))],
            'hazards_identified' => 'nullable|string|max:3000',
            'safety_precautions' => 'nullable|string|max:3000',
            'ppe_requirements'   => 'nullable|string|max:1000',
            'valid_from'         => 'required|date',
            'valid_until'        => 'required|date|after:valid_from',
            'attachments.*'      => 'nullable|' . AttachmentService::rule(),
        ]);
    }

    protected function formData(): array
    {
        return [
            'assets'     => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']),
            'workOrders' => WorkOrder::whereNotIn('status', ['closed', 'cancelled'])->latest()->get(['id', 'wo_no', 'asset_id']),
        ];
    }
}
