<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Department;
use App\Models\MaintenanceRequest;
use App\Models\Spare;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorService;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use App\Services\AttachmentService;
use App\Services\WorkOrderService;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function __construct(protected WorkOrderService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $workOrders = WorkOrder::with(['asset', 'technician', 'team', 'request'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('wo_no', 'like', "%$v%")->orWhere('description', 'like', "%$v%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->open, fn ($q) => $q->whereNotIn('status', ['closed', 'cancelled']))
            ->when($request->overdue, fn ($q) => $q->whereNotIn('status', ['closed', 'cancelled'])->whereDate('planned_end', '<', today()))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($request->work_type, fn ($q, $v) => $q->where('work_type', $v))
            ->when($request->source, fn ($q, $v) => $q->where('source', $v))
            ->when($request->assigned_to, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($request->mine, fn ($q) => $q->where('assigned_to', $user->id))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByRaw("FIELD(status,'open','assigned','in_progress','on_hold','verification_pending','completed','closed','cancelled')")
            ->latest()
            ->paginate(20)->withQueryString();

        return view('work-orders.index', [
            'workOrders'  => $workOrders,
            'technicians' => User::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('work-orders.form', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id'            => 'required|exists:pm_assets,id',
            'department_id'       => 'nullable|exists:pm_departments,id',
            'work_type'           => 'required|string|max:40',
            'priority'            => 'required|in:critical,high,medium,low',
            'team_id'             => 'nullable|exists:pm_teams,id',
            'assigned_to'         => 'nullable|exists:users,id',
            'planned_start'       => 'required|date',
            'planned_end'         => 'required|date|after_or_equal:planned_start',
            'description'         => 'required|string|max:3000',
            'estimated_cost'      => 'nullable|numeric|min:0',
            'safety_requirements' => 'nullable|string|max:2000',
            'permit_required'     => 'boolean',
            'remarks'             => 'nullable|string|max:2000',
        ]);
        $data['permit_required'] = $request->boolean('permit_required');

        $workOrder = $this->service->createManual($data);
        flash_toast('success', 'Work order ' . $workOrder->wo_no . ' created.');
        return redirect()->route('work-orders.show', $workOrder);
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['request.requester', 'request.latestAssessment.spares.spare', 'asset', 'department', 'team', 'technician', 'vendor', 'verifier', 'closer',
            'tasks', 'labour.user', 'verifications.verifier', 'stockTransactions.spare', 'stockTransactions.user', 'permits', 'pmSchedule.plan.checklist.items', 'pmLog', 'attachments.uploader', 'history.user', 'vendorServices.vendor', 'purchaseRequests.spare']);

        return view('work-orders.show', [
            'wo'          => $workOrder,
            'teams'       => Team::where('is_active', true)->get(),
            'technicians' => User::where('is_active', true)->orderBy('name')->get(),
            'spares'      => Spare::where('is_active', true)->orderBy('name')->get(),
            'vendors'     => Vendor::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function print(WorkOrder $workOrder)
    {
        $workOrder->load(['request', 'asset', 'team', 'technician', 'tasks', 'labour.user', 'stockTransactions.spare', 'verifications.verifier', 'permits']);
        return view('work-orders.print', ['wo' => $workOrder]);
    }

    // ------------------------------------------------------------ Lifecycle

    public function assign(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'team_id'       => 'nullable|exists:pm_teams,id',
            'assigned_to'   => 'required|exists:users,id',
            'planned_start' => 'required|date',
            'planned_end'   => 'required|date|after_or_equal:planned_start',
            'safety_requirements' => 'nullable|string|max:2000',
            'permit_required'     => 'boolean',
        ]);
        // Safety rule: once a permit is required (by the technical assessment) it cannot be switched off here
        $data['permit_required'] = $request->boolean('permit_required') || $workOrder->permit_required;
        $this->service->assign($workOrder, $data);
        flash_toast('success', 'Work order assigned.');
        return back();
    }

    public function start(WorkOrder $workOrder)
    {
        $this->authorizeTechnician($workOrder);
        $this->service->start($workOrder);
        flash_toast('success', 'Work started.');
        return back();
    }

    public function hold(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeTechnician($workOrder);
        $data = $request->validate(['hold_reason' => 'required|string|min:3|max:1000']);
        $this->service->hold($workOrder, $data['hold_reason']);
        flash_toast('warning', 'Work order put on hold.');
        return back();
    }

    public function complete(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeTechnician($workOrder);
        $data = $request->validate([
            'actual_end'           => 'nullable|date',
            'actual_problem_found' => 'required|string|max:3000',
            'work_performed'       => 'required|string|max:3000',
            'repair_remarks'       => 'nullable|string|max:2000',
            'downtime_hours'       => 'nullable|numeric|min:0',
            'vendor_id'            => 'nullable|exists:pm_vendors,id',
            'vendor_details'       => 'nullable|string|max:255',
            'other_cost'           => 'nullable|numeric|min:0',
            'before_photos.*'      => 'nullable|' . AttachmentService::rule(),
            'after_photos.*'       => 'nullable|' . AttachmentService::rule(),
        ]);

        $this->service->complete($workOrder, $data, $request->file('before_photos'), $request->file('after_photos'));
        flash_toast('success', 'Work recorded. Sent for supervisor verification.');
        return back();
    }

    public function verify(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'decision'             => 'required|in:approved,sent_back',
            'machine_operation_ok' => 'boolean',
            'safety_ok'            => 'boolean',
            'repair_quality_ok'    => 'boolean',
            'production_ready'     => 'boolean',
            'test_results'         => 'nullable|string|max:2000',
            'observations'         => 'nullable|string|max:2000',
            'remarks'              => 'required|string|min:3|max:2000',
            'final_machine_status' => 'nullable|in:active,idle,under_maintenance,breakdown,decommissioned',
            'actual_cost'          => 'nullable|numeric|min:0',
        ]);
        foreach (['machine_operation_ok', 'safety_ok', 'repair_quality_ok', 'production_ready'] as $f) {
            $data[$f] = $request->boolean($f);
        }

        if ($data['decision'] === 'approved' && !($data['machine_operation_ok'] && $data['safety_ok'] && $data['repair_quality_ok'])) {
            flash_toast('error', 'Machine operation, safety and repair quality must all be confirmed to close the work order.');
            return back()->withInput();
        }

        $this->service->verify($workOrder, $data);
        flash_toast('success', $data['decision'] === 'approved' ? 'Work order verified and closed.' : 'Work order sent back to technician.');
        return back();
    }

    public function cancel(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['reason' => 'required|string|min:3|max:1000']);
        $this->service->cancel($workOrder, $data['reason']);
        flash_toast('success', 'Work order cancelled.');
        return back();
    }

    // ------------------------------------------------------------ Sub records

    public function storeTask(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['description' => 'required|string|max:255']);
        $workOrder->tasks()->create($data);
        flash_toast('success', 'Task added.');
        return back();
    }

    public function toggleTask(WorkOrder $workOrder, WorkOrderTask $task)
    {
        abort_unless($task->work_order_id === $workOrder->id, 404);
        $task->update(['is_done' => !$task->is_done, 'done_by' => $task->is_done ? null : auth()->id(), 'done_at' => $task->is_done ? null : now()]);
        return back();
    }

    public function storeLabour(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'work_date' => 'required|date',
            'hours'     => 'required|numeric|min:0.25|max:24',
            'rate'      => 'nullable|numeric|min:0',
            'remarks'   => 'nullable|string|max:255',
        ]);
        $data['rate'] = $data['rate'] ?? setting('pm.labour_rate', 0);
        $workOrder->labour()->create($data);
        $workOrder->update(['labour_hours' => $workOrder->labour()->sum('hours')]);
        flash_toast('success', 'Labour hours recorded.');
        return back();
    }

    public function storePhotos(Request $request, WorkOrder $workOrder)
    {
        $request->validate(['category' => 'required|in:before_photo,after_photo,document', 'attachments.*' => AttachmentService::rule()]);
        AttachmentService::storeMany($workOrder, $request->file('attachments'), $request->category);
        flash_toast('success', 'Files uploaded.');
        return back();
    }

    public function storeVendorService(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'vendor_id'             => 'required|exists:pm_vendors,id',
            'service_date'          => 'required|date',
            'response_hours'        => 'nullable|numeric|min:0',
            'resolution_hours'      => 'nullable|numeric|min:0',
            'response_within_sla'   => 'boolean',
            'resolution_within_sla' => 'boolean',
            'work_quality'          => 'required|integer|min:1|max:5',
            'attended'              => 'boolean',
            'repeat_failure'        => 'boolean',
            'cost'                  => 'nullable|numeric|min:0',
            'feedback'              => 'nullable|string|max:1000',
        ]);
        foreach (['response_within_sla', 'resolution_within_sla', 'attended', 'repeat_failure'] as $f) {
            $data[$f] = $request->boolean($f);
        }
        VendorService::create($data + ['work_order_id' => $workOrder->id, 'recorded_by' => auth()->id()]);
        flash_toast('success', 'Vendor service recorded.');
        return back();
    }

    // ------------------------------------------------------------ Helpers

    /** Technician actions: only the assigned technician, their team lead, or a supervisor. */
    protected function authorizeTechnician(WorkOrder $workOrder): void
    {
        $user = auth()->user();
        if ($user->hasAnyPermission(['pm.workorders.assign', 'pm.workorders.verify'])) {
            return;
        }
        if ($workOrder->assigned_to && $workOrder->assigned_to !== $user->id && !$workOrder->team?->members->contains($user)) {
            abort(403, 'This work order is assigned to someone else.');
        }
    }

    protected function formData(): array
    {
        return [
            'assets'      => Asset::whereNotIn('status', ['decommissioned'])->orderBy('asset_code')->get(['id', 'asset_code', 'name', 'department_id']),
            'departments' => Department::orderBy('name')->get(),
            'teams'       => Team::where('is_active', true)->get(),
            'technicians' => User::where('is_active', true)->orderBy('name')->get(),
            'types'       => MaintenanceRequest::TYPES,
        ];
    }
}
