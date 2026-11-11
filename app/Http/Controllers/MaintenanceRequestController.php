<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentRequest;
use App\Http\Requests\InspectionRequest;
use App\Http\Requests\MaintenanceRequestFormRequest;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\MaintenanceRequest;
use App\Models\Shift;
use App\Models\Spare;
use App\Models\Vendor;
use App\Services\ApprovalWorkflowService;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        protected MaintenanceRequestService $requests,
        protected ApprovalWorkflowService $workflow
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = MaintenanceRequest::with(['asset', 'requester', 'department'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('request_no', 'like', "%$v%")->orWhere('problem_description', 'like', "%$v%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->open, fn ($q) => $q->whereNotIn('status', ['closed', 'cancelled', 'rejected']))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($request->request_type, fn ($q, $v) => $q->where('request_type', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->asset_id, fn ($q, $v) => $q->where('asset_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('request_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('request_date', '<=', $v))
            ->when($request->mine, fn ($q) => $q->where('requested_by', $user->id));

        // "Employee" level users only see their own requests
        if (!$user->hasAnyPermission(['pm.requests.manage', 'pm.requests.inspect', 'pm.requests.assess', 'pm.requests.approve', 'pm.workorders.view', 'pm.reports.view'])) {
            $query->where('requested_by', $user->id);
        }

        $requests = $query->orderByRaw("FIELD(priority,'critical','high','medium','low')")->latest('request_date')->paginate(20)->withQueryString();

        return view('requests.index', [
            'requests'    => $requests,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('requests.form', [
            'maintenanceRequest' => new MaintenanceRequest(['asset_id' => $request->asset_id, 'priority' => 'medium', 'request_type' => 'breakdown']),
        ] + $this->formData());
    }

    public function store(MaintenanceRequestFormRequest $request)
    {
        $mr = $this->requests->create($request->validated(), $request->file('attachments'));
        flash_toast('success', 'Request ' . $mr->request_no . ' submitted. Inspector has been notified.');
        return redirect()->route('requests.show', $mr);
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $user = auth()->user();
        if (!$user->hasAnyPermission(['pm.requests.manage', 'pm.requests.inspect', 'pm.requests.assess', 'pm.requests.approve', 'pm.workorders.view', 'pm.reports.view'])
            && $maintenanceRequest->requested_by !== $user->id) {
            abort(403, 'You can only view your own requests.');
        }

        $maintenanceRequest->load(['asset', 'component', 'department', 'requester', 'shift', 'problemCategory', 'emergencyApprover',
            'inspections.inspector', 'inspections.attachments', 'assessments.assessor', 'assessments.vendor', 'assessments.spares.spare', 'assessments.attachments',
            'approvals.approver', 'workOrders.technician', 'attachments.uploader', 'history.user', 'purchaseRequests.spare']);

        return view('requests.show', [
            'r'       => $maintenanceRequest,
            'spares'  => Spare::where('is_active', true)->orderBy('name')->get(),
            'vendors' => Vendor::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(MaintenanceRequest $maintenanceRequest)
    {
        abort_unless($maintenanceRequest->canBeEditedBy(auth()->user()), 403, 'This request can no longer be edited.');
        return view('requests.form', ['maintenanceRequest' => $maintenanceRequest] + $this->formData());
    }

    public function update(MaintenanceRequestFormRequest $request, MaintenanceRequest $maintenanceRequest)
    {
        abort_unless($maintenanceRequest->canBeEditedBy(auth()->user()), 403, 'This request can no longer be edited.');
        $this->requests->update($maintenanceRequest, $request->validated(), $request->file('attachments'));
        flash_toast('success', 'Request updated.');
        return redirect()->route('requests.show', $maintenanceRequest);
    }

    // ------------------------------------------------------------ Workflow actions

    public function storeInspection(InspectionRequest $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->workflow->recordInspection($maintenanceRequest, $request->validated(), $request->file('attachments'));
        flash_toast('success', $request->recommendation === 'reject' ? 'Request rejected at inspection.' : 'Inspection recorded. Sent for technical assessment.');
        return redirect()->route('requests.show', $maintenanceRequest);
    }

    public function storeAssessment(AssessmentRequest $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validated();
        $spares = $data['spares'] ?? [];
        unset($data['spares']);

        $this->workflow->recordAssessment($maintenanceRequest, $data, $spares, $request->file('attachments'));
        flash_toast('success', 'Technical assessment saved. Sent for budget approval.');
        return redirect()->route('requests.show', $maintenanceRequest);
    }

    public function storeApproval(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'action'        => 'required|in:approved,rejected,sent_back',
            'approved_cost' => 'nullable|numeric|min:0',
            'remarks'       => 'required|string|min:3|max:2000',
        ]);

        $this->workflow->decide($maintenanceRequest, $data['action'], $data['remarks'], isset($data['approved_cost']) ? (float) $data['approved_cost'] : null);

        flash_toast('success', match ($data['action']) {
            'approved'  => 'Request approved and work order generated.',
            'rejected'  => 'Request rejected.',
            'sent_back' => 'Request sent back for clarification.',
        });
        return redirect()->route('requests.show', $maintenanceRequest);
    }

    public function emergency(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate(['emergency_reason' => 'required|string|min:10|max:2000']);
        $this->workflow->emergencyApprove($maintenanceRequest, $data['emergency_reason']);
        flash_toast('warning', 'Emergency work order generated. A retrospective review is required.');
        return redirect()->route('requests.show', $maintenanceRequest);
    }

    public function emergencyReview(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate(['remarks' => 'required|string|min:3|max:2000']);
        $this->workflow->emergencyReview($maintenanceRequest, $data['remarks']);
        flash_toast('success', 'Emergency review recorded.');
        return back();
    }

    public function resubmit(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        abort_unless($maintenanceRequest->status === 'sent_back', 403);
        $data = $request->validate(['remarks' => 'required|string|min:3|max:2000']);
        $this->requests->resubmit($maintenanceRequest, $data['remarks']);
        flash_toast('success', 'Request re-submitted for approval.');
        return back();
    }

    public function cancel(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $user = $request->user();
        abort_unless($maintenanceRequest->isOpen() && ($maintenanceRequest->requested_by === $user->id || $user->hasPermission('pm.requests.manage')), 403);
        if ($maintenanceRequest->workOrders()->whereNotIn('status', ['cancelled'])->exists()) {
            flash_toast('error', 'Cancel the work order first.');
            return back();
        }
        $data = $request->validate(['reason' => 'required|string|min:3|max:1000']);
        $this->requests->cancel($maintenanceRequest, $data['reason']);
        flash_toast('success', 'Request cancelled.');
        return back();
    }

    protected function formData(): array
    {
        return [
            'assets'      => Asset::whereNotIn('status', ['decommissioned'])->orderBy('asset_code')->get(['id', 'asset_code', 'name', 'department_id', 'parent_id']),
            'departments' => Department::orderBy('name')->get(),
            'shifts'      => Shift::where('is_active', true)->get(),
            'problems'    => Category::ofType('problem')->get(),
        ];
    }
}
