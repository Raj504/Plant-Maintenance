<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\MaintenanceRequest;
use App\Models\RequestInspection;
use App\Models\TechnicalAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The mandatory sequential workflow:
 *
 *   submitted -> inspection -> technical assessment -> budget approval -> work order
 *
 * Each method checks the request is in the right status, saves a permanent
 * record, moves the status forward, writes history and notifies the next role.
 */
class ApprovalWorkflowService
{
    public function __construct(protected WorkOrderService $workOrders)
    {
    }

    // ------------------------------------------------------------ Step 2: Inspection

    public function recordInspection(MaintenanceRequest $request, array $data, ?array $files = null): RequestInspection
    {
        $this->assertStatus($request, ['submitted', 'inspection_pending']);

        return DB::transaction(function () use ($request, $data, $files) {
            $inspection = $request->inspections()->create(array_merge($data, [
                'inspector_id'    => auth()->id(),
                'inspection_date' => $data['inspection_date'] ?? now(),
            ]));
            AttachmentService::storeMany($inspection, $files, 'inspection');

            $from = $request->status;

            if (($data['recommendation'] ?? 'proceed') === 'reject') {
                $request->update(['status' => 'rejected', 'closed_at' => now(), 'closed_by' => auth()->id(), 'closure_remarks' => $data['rejection_reason']]);
                $request->logHistory('rejected', 'Rejected at inspection: ' . $data['rejection_reason'], $from, 'rejected');
                NotificationService::notifyUser($request->requested_by, 'request_rejected',
                    'Request ' . $request->request_no . ' rejected at inspection', $data['rejection_reason'], route('requests.show', $request), 'danger');
            } else {
                $request->update(['status' => 'assessment_pending']);
                $request->logHistory('inspected', 'Inspection recorded. ' . ($data['remarks'] ?? ''), $from, 'assessment_pending');
                NotificationService::notifyPermission('pm.requests.assess', 'assessment_pending',
                    'Technical assessment pending for ' . $request->request_no,
                    $request->asset->name, route('requests.show', $request));
            }

            return $inspection;
        });
    }

    // ------------------------------------------------------------ Step 3: Technical assessment

    public function recordAssessment(MaintenanceRequest $request, array $data, array $spares = [], ?array $files = null): TechnicalAssessment
    {
        $this->assertStatus($request, ['assessment_pending']);

        return DB::transaction(function () use ($request, $data, $spares, $files) {
            $data['total_estimated_cost'] = ($data['estimated_labour_cost'] ?? 0)
                + ($data['estimated_material_cost'] ?? 0)
                + ($data['estimated_service_cost'] ?? 0);

            $assessment = $request->assessments()->create(array_merge($data, [
                'assessed_by'     => auth()->id(),
                'assessment_date' => $data['assessment_date'] ?? now(),
            ]));

            foreach ($spares as $row) {
                if (empty($row['spare_id']) && empty($row['spare_name'])) {
                    continue;
                }
                $assessment->spares()->create([
                    'spare_id'       => $row['spare_id'] ?: null,
                    'spare_name'     => $row['spare_name'] ?? null,
                    'quantity'       => $row['quantity'] ?? 1,
                    'estimated_cost' => $row['estimated_cost'] ?? 0,
                ]);
            }
            AttachmentService::storeMany($assessment, $files, 'assessment');

            $from = $request->status;
            $request->update(['status' => 'approval_pending', 'estimated_cost' => $data['total_estimated_cost']]);
            $request->logHistory('assessed', 'Technical assessment recorded. Estimated cost ' . money($data['total_estimated_cost']), $from, 'approval_pending');

            NotificationService::notifyPermission('pm.requests.approve', 'approval_pending',
                'Budget approval pending for ' . $request->request_no,
                'Estimated cost ' . money($data['total_estimated_cost']), route('requests.show', $request), 'warning');

            return $assessment;
        });
    }

    // ------------------------------------------------------------ Step 4: Budget / admin approval

    public function decide(MaintenanceRequest $request, string $action, string $remarks, ?float $approvedCost = null): Approval
    {
        $this->assertStatus($request, ['approval_pending']);

        return DB::transaction(function () use ($request, $action, $remarks, $approvedCost) {
            $approval = $request->approvals()->create([
                'stage'          => 'budget',
                'approver_id'    => auth()->id(),
                'action'         => $action,
                'estimated_cost' => $request->estimated_cost,
                'approved_cost'  => $action === 'approved' ? ($approvedCost ?? $request->estimated_cost) : null,
                'remarks'        => $remarks,
                'acted_at'       => now(),
            ]);

            $from = $request->status;

            switch ($action) {
                case 'approved':
                    $request->update(['status' => 'approved', 'approved_cost' => $approval->approved_cost]);
                    $request->logHistory('approved', 'Approved. Approved cost ' . money($approval->approved_cost) . '. ' . $remarks, $from, 'approved');
                    // Step 5: generate the work order automatically
                    $workOrder = $this->workOrders->createFromRequest($request);
                    NotificationService::notifyUser($request->requested_by, 'request_approved',
                        'Request ' . $request->request_no . ' approved', 'Work order ' . $workOrder->wo_no . ' created.', route('work-orders.show', $workOrder), 'success');
                    break;

                case 'rejected':
                    $request->update(['status' => 'rejected', 'closed_at' => now(), 'closed_by' => auth()->id(), 'closure_remarks' => $remarks]);
                    $request->logHistory('rejected', $remarks, $from, 'rejected');
                    NotificationService::notifyUser($request->requested_by, 'request_rejected',
                        'Request ' . $request->request_no . ' rejected', $remarks, route('requests.show', $request), 'danger');
                    break;

                case 'sent_back':
                    $request->update(['status' => 'sent_back']);
                    $request->logHistory('sent_back', $remarks, $from, 'sent_back');
                    NotificationService::notifyUser($request->requested_by, 'request_sent_back',
                        'Request ' . $request->request_no . ' sent back for clarification', $remarks, route('requests.show', $request), 'warning');
                    if ($request->latestAssessment) {
                        NotificationService::notifyUser($request->latestAssessment->assessed_by, 'request_sent_back',
                            'Request ' . $request->request_no . ' sent back for clarification', $remarks, route('requests.show', $request), 'warning');
                    }
                    break;
            }

            return $approval;
        });
    }

    // ------------------------------------------------------------ Emergency path

    /**
     * Emergency maintenance: an authorised approver can bypass the sequence.
     * A work order is created immediately; the request is flagged for retrospective review.
     */
    public function emergencyApprove(MaintenanceRequest $request, string $reason): void
    {
        $this->assertStatus($request, ['submitted', 'inspection_pending', 'assessment_pending', 'approval_pending', 'sent_back']);

        DB::transaction(function () use ($request, $reason) {
            $from = $request->status;
            $request->update([
                'is_emergency'          => true,
                'emergency_reason'      => $reason,
                'emergency_approved_by' => auth()->id(),
                'emergency_approved_at' => now(),
                'status'                => 'approved',
            ]);
            $request->logHistory('emergency_approved', 'EMERGENCY: ' . $reason, $from, 'approved');

            $workOrder = $this->workOrders->createFromRequest($request, 'emergency');

            NotificationService::notifyPermission('pm.requests.approve', 'emergency',
                'Emergency work order ' . $workOrder->wo_no . ' raised', $reason, route('work-orders.show', $workOrder), 'danger');
        });
    }

    /** Retrospective technical / administrative review of an emergency job. */
    public function emergencyReview(MaintenanceRequest $request, string $remarks): void
    {
        DB::transaction(function () use ($request, $remarks) {
            $request->update(['emergency_reviewed' => true, 'emergency_review_remarks' => $remarks]);
            $request->approvals()->create([
                'stage'          => 'emergency_review',
                'approver_id'    => auth()->id(),
                'action'         => 'approved',
                'estimated_cost' => $request->estimated_cost,
                'approved_cost'  => $request->approved_cost,
                'remarks'        => $remarks,
                'acted_at'       => now(),
            ]);
            $request->logHistory('emergency_reviewed', $remarks);
        });
    }

    // ------------------------------------------------------------ Helper

    protected function assertStatus(MaintenanceRequest $request, array $allowed): void
    {
        if (!in_array($request->status, $allowed)) {
            throw ValidationException::withMessages([
                'status' => 'This action is not allowed while the request is "' . status_label($request->status) . '".',
            ]);
        }
    }
}
