<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Shift;
use App\Models\ShiftHandover;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\DocumentNumberService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ShiftHandoverController extends Controller
{
    public function index(Request $request)
    {
        $handovers = ShiftHandover::with(['fromShift', 'toShift', 'department', 'handedOverBy', 'receivedBy'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('handover_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('handover_date', '<=', $v))
            ->latest('handover_date')->latest('id')->paginate(20)->withQueryString();

        return view('handovers.index', ['handovers' => $handovers, 'departments' => Department::orderBy('name')->get()]);
    }

    public function create()
    {
        // Pre-fill pending work orders and breakdowns so the operator just reviews them
        $openWo = WorkOrder::with('asset')->whereNotIn('status', ['closed', 'cancelled'])->get()
            ->map(fn ($w) => $w->wo_no . ' - ' . $w->asset->name . ' (' . status_label($w->status) . ')')->join("\n");
        $breakdowns = \App\Models\Asset::where('status', 'breakdown')->get()->map(fn ($a) => $a->asset_code . ' - ' . $a->name)->join("\n");
        $shortage = \App\Models\Spare::whereColumn('current_stock', '<=', 'reorder_level')->where('is_active', true)->get()->map(fn ($s) => $s->code . ' - ' . $s->name . ' (stock ' . ($s->current_stock + 0) . ')')->join("\n");

        return view('handovers.form', [
            'handover'    => new ShiftHandover(['handover_date' => today(), 'pending_work_orders' => $openWo, 'breakdown_status' => $breakdowns, 'spare_shortage' => $shortage]),
            'shifts'      => Shift::where('is_active', true)->get(),
            'departments' => Department::orderBy('name')->get(),
            'users'       => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'handover_date'              => 'required|date',
            'from_shift_id'              => 'required|exists:pm_shifts,id',
            'to_shift_id'                => 'required|exists:pm_shifts,id|different:from_shift_id',
            'department_id'              => 'nullable|exists:pm_departments,id',
            'received_by'                => 'nullable|exists:users,id',
            'pending_work_orders'        => 'nullable|string',
            'breakdown_status'           => 'nullable|string',
            'machines_under_observation' => 'nullable|string',
            'temporary_repairs'          => 'nullable|string',
            'safety_issues'              => 'nullable|string',
            'spare_shortage'             => 'nullable|string',
            'instructions'               => 'nullable|string',
            'remarks'                    => 'nullable|string',
        ]);
        $data['handover_no']     = DocumentNumberService::next('handover');
        $data['handed_over_by']  = auth()->id();
        $data['status']          = 'pending';

        $handover = ShiftHandover::create($data);
        $handover->logHistory('created', 'Handover submitted', null, 'pending');

        if ($handover->received_by) {
            NotificationService::notifyUser($handover->received_by, 'handover_pending', 'Shift handover ' . $handover->handover_no . ' awaiting your acknowledgement', null, route('handovers.show', $handover), 'warning');
        } else {
            NotificationService::notifyPermission('pm.handover.manage', 'handover_pending', 'Shift handover ' . $handover->handover_no . ' awaiting acknowledgement', null, route('handovers.show', $handover), 'warning');
        }

        flash_toast('success', 'Handover ' . $handover->handover_no . ' submitted.');
        return redirect()->route('handovers.show', $handover);
    }

    public function show(ShiftHandover $handover)
    {
        $handover->load(['fromShift', 'toShift', 'department', 'handedOverBy', 'receivedBy', 'history.user']);
        return view('handovers.show', compact('handover'));
    }

    public function acknowledge(Request $request, ShiftHandover $handover)
    {
        abort_if($handover->status === 'acknowledged', 403);
        if ($handover->handed_over_by === auth()->id()) {
            flash_toast('error', 'The handover must be acknowledged by the receiving person.');
            return back();
        }
        $data = $request->validate(['acknowledgement_remarks' => 'nullable|string|max:2000']);
        $handover->update(['status' => 'acknowledged', 'received_by' => auth()->id(), 'acknowledged_at' => now(), 'acknowledgement_remarks' => $data['acknowledgement_remarks'] ?? null]);
        $handover->logHistory('acknowledged', $data['acknowledgement_remarks'] ?? 'Handover received', 'pending', 'acknowledged');
        flash_toast('success', 'Handover acknowledged.');
        return back();
    }
}
