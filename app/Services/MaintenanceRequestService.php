<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\DB;

/**
 * Creating / updating maintenance requests (complaints).
 * Workflow steps (inspection, assessment, approval) live in ApprovalWorkflowService.
 */
class MaintenanceRequestService
{
    public function create(array $data, ?array $files = null): MaintenanceRequest
    {
        return DB::transaction(function () use ($data, $files) {
            $asset = Asset::findOrFail($data['asset_id']);

            $request = MaintenanceRequest::create(array_merge($data, [
                'request_no'    => DocumentNumberService::next('request'),
                'request_date'  => $data['request_date'] ?? now(),
                'department_id' => $data['department_id'] ?? $asset->department_id,
                'requested_by'  => auth()->id(),
                'status'        => 'submitted',
            ]));

            AttachmentService::storeMany($request, $files, 'complaint');

            $request->logHistory('created', 'Request submitted', null, 'submitted');

            // Breakdown with machine stopped -> mark asset as breakdown
            if (!empty($data['machine_stopped']) && $asset->status === 'active') {
                $asset->update(['status' => 'breakdown']);
                $asset->logHistory('status_changed', 'Marked breakdown by request ' . $request->request_no, 'active', 'breakdown');
            }

            // Tell the inspectors
            $level = $request->priority === 'critical' ? 'danger' : 'info';
            NotificationService::notifyPermission(
                'pm.requests.inspect',
                'request_new',
                ($request->priority === 'critical' ? 'CRITICAL: ' : '') . 'New maintenance request ' . $request->request_no,
                $asset->name . ' - ' . \Illuminate\Support\Str::limit($request->problem_description, 80),
                route('requests.show', $request),
                $level
            );

            return $request;
        });
    }

    public function update(MaintenanceRequest $request, array $data, ?array $files = null): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $data, $files) {
            $old = $request->only(array_keys($data));
            $request->update($data);
            AttachmentService::storeMany($request, $files, 'complaint');

            $changes = [];
            foreach ($data as $key => $value) {
                if (($old[$key] ?? null) != $value) {
                    $changes[$key] = ['old' => $old[$key] ?? null, 'new' => $value];
                }
            }
            if ($changes) {
                $request->logHistory('updated', 'Request details updated', null, null, $changes);
            }
            return $request;
        });
    }

    /** Re-submit after "sent back": goes straight back to approval with history preserved. */
    public function resubmit(MaintenanceRequest $request, string $remarks): void
    {
        DB::transaction(function () use ($request, $remarks) {
            $from = $request->status;
            $request->update(['status' => 'approval_pending']);
            $request->logHistory('resubmitted', $remarks, $from, 'approval_pending');

            NotificationService::notifyPermission(
                'pm.requests.approve',
                'approval_pending',
                'Request ' . $request->request_no . ' re-submitted for approval',
                $remarks,
                route('requests.show', $request)
            );
        });
    }

    public function cancel(MaintenanceRequest $request, string $reason): void
    {
        DB::transaction(function () use ($request, $reason) {
            $from = $request->status;
            $request->update(['status' => 'cancelled', 'closure_remarks' => $reason, 'closed_at' => now(), 'closed_by' => auth()->id()]);
            $request->logHistory('cancelled', $reason, $from, 'cancelled');
        });
    }
}
