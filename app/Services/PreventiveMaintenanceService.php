<?php

namespace App\Services;

use App\Models\PmLog;
use App\Models\PmPlan;
use App\Models\PmSchedule;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

/**
 * Preventive maintenance engine.
 *
 * - calculateNextDue()   -> after a PM is done, work out the next date / hours
 * - generateDueSchedules() -> called by the scheduler. Creates ONE schedule + work order per plan
 *                           that is due. Safe to run many times (checks for an open schedule first).
 * - completeFromWorkOrder() -> when a PM work order is verified, record the log and roll the plan forward.
 */
class PreventiveMaintenanceService
{
    public function createPlan(array $data): PmPlan
    {
        $data['plan_no'] = DocumentNumberService::next('pm_plan');
        $data['created_by'] = auth()->id();
        $data = $this->applyFrequency($data);
        $data = $this->fillNextDue($data);

        $plan = PmPlan::create($data);
        $plan->logHistory('created', 'PM plan created');
        return $plan;
    }

    public function updatePlan(PmPlan $plan, array $data): PmPlan
    {
        $data = $this->applyFrequency($data);
        $data = $this->fillNextDue($data);
        $plan->update($data);
        $plan->logHistory('updated', 'PM plan updated');
        return $plan;
    }

    /** Convert frequency keyword (monthly...) into interval_days. */
    protected function applyFrequency(array $data): array
    {
        $freq = $data['frequency'] ?? null;
        if ($freq && $freq !== 'custom' && isset(PmPlan::FREQUENCIES[$freq])) {
            $data['interval_days'] = PmPlan::FREQUENCIES[$freq];
        }
        return $data;
    }

    /** If next due values are blank, compute them from last done + interval. */
    protected function fillNextDue(array $data): array
    {
        $trigger = $data['trigger_type'] ?? 'calendar';

        if (in_array($trigger, ['calendar', 'both']) && empty($data['next_due_date'])) {
            $base = !empty($data['last_done_date']) ? \Carbon\Carbon::parse($data['last_done_date']) : now();
            $data['next_due_date'] = $base->copy()->addDays((int) ($data['interval_days'] ?? 30))->toDateString();
        }
        if (in_array($trigger, ['running_hours', 'both']) && (!isset($data['next_due_hours']) || $data['next_due_hours'] === '' || $data['next_due_hours'] === null)) {
            $last = (float) ($data['last_done_hours'] ?? 0);
            $data['next_due_hours'] = $last + (float) ($data['interval_hours'] ?? 0);
        }
        if ($trigger === 'calendar') {
            $data['interval_hours'] = null;
            $data['next_due_hours'] = null;
        }
        if ($trigger === 'running_hours') {
            $data['interval_days'] = null;
            $data['next_due_date'] = null;
        }
        return $data;
    }

    /**
     * Create a schedule (and work order) for every active plan that is due.
     * Returns the number of schedules created. Never creates a second open schedule for the same plan.
     */
    public function generateDueSchedules(int $lookAheadDays = 0): int
    {
        $created = 0;
        $plans = PmPlan::with(['asset', 'openSchedule'])->where('is_active', true)->get();

        foreach ($plans as $plan) {
            if ($plan->openSchedule) {
                continue; // one open cycle at a time -> no duplicates
            }

            $reason = $this->dueReasonWithLookAhead($plan, $lookAheadDays);
            if (!$reason) {
                continue;
            }

            DB::transaction(function () use ($plan, $reason, &$created) {
                $schedule = PmSchedule::create([
                    'plan_id'        => $plan->id,
                    'due_date'       => $plan->next_due_date,
                    'due_hours'      => $plan->next_due_hours,
                    'trigger_reason' => $reason,
                    'status'         => 'due',
                    'generated_at'   => now(),
                ]);

                $workOrder = app(WorkOrderService::class)->createFromSchedule($schedule);
                $schedule->update(['work_order_id' => $workOrder->id, 'status' => 'in_progress']);
                $created++;
            });
        }

        return $created;
    }

    protected function dueReasonWithLookAhead(PmPlan $plan, int $lookAheadDays): ?string
    {
        if ($plan->usesCalendar() && $plan->next_due_date && $plan->next_due_date->lte(now()->addDays($lookAheadDays)->endOfDay())) {
            return 'calendar';
        }
        if ($plan->usesRunningHours() && $plan->next_due_hours !== null && $plan->asset
            && (float) $plan->asset->current_running_hours >= (float) $plan->next_due_hours) {
            return 'running_hours';
        }
        return null;
    }

    /** Manual "generate now" from the UI for one plan. */
    public function generateForPlan(PmPlan $plan): ?PmSchedule
    {
        if ($plan->openSchedule) {
            return null;
        }
        return DB::transaction(function () use ($plan) {
            $schedule = PmSchedule::create([
                'plan_id'        => $plan->id,
                'due_date'       => $plan->next_due_date ?? now(),
                'due_hours'      => $plan->next_due_hours,
                'trigger_reason' => 'manual',
                'status'         => 'due',
                'generated_at'   => now(),
            ]);
            $workOrder = app(WorkOrderService::class)->createFromSchedule($schedule);
            $schedule->update(['work_order_id' => $workOrder->id, 'status' => 'in_progress']);
            return $schedule;
        });
    }

    /**
     * Record the completed PM with its checklist results and calculate the next due point.
     * $items = [ ['item_text' => ..., 'result' => ok|not_ok|na, 'remarks' => ...], ... ]
     */
    public function recordCompletion(PmPlan $plan, array $data, array $items = [], ?PmSchedule $schedule = null, ?WorkOrder $workOrder = null, ?array $files = null): PmLog
    {
        return DB::transaction(function () use ($plan, $data, $items, $schedule, $workOrder, $files) {
            $completedDate = \Carbon\Carbon::parse($data['completed_date'] ?? now());
            $hours = isset($data['running_hours_at_completion']) && $data['running_hours_at_completion'] !== ''
                ? (float) $data['running_hours_at_completion']
                : (float) $plan->asset->current_running_hours;

            $log = PmLog::create([
                'plan_id'                     => $plan->id,
                'schedule_id'                 => $schedule?->id,
                'work_order_id'               => $workOrder?->id,
                'asset_id'                    => $plan->component_id ?: $plan->asset_id,
                'completed_by'                => auth()->id() ?? $workOrder?->assigned_to,
                'completed_date'              => $completedDate,
                'running_hours_at_completion' => $hours,
                'duration_hours'              => $data['duration_hours'] ?? null,
                'actual_cost'                 => $data['actual_cost'] ?? 0,
                'observations'                => $data['observations'] ?? null,
                'remarks'                     => $data['remarks'] ?? null,
            ]);

            foreach ($items as $item) {
                if (empty($item['item_text'])) {
                    continue;
                }
                $log->items()->create([
                    'item_text' => $item['item_text'],
                    'result'    => $item['result'] ?? 'ok',
                    'remarks'   => $item['remarks'] ?? null,
                ]);
            }
            AttachmentService::storeMany($log, $files, 'pm_evidence');

            if ($schedule) {
                $schedule->update(['status' => 'completed', 'completed_at' => now()]);
            }

            // Roll the plan forward
            $update = ['last_done_date' => $completedDate, 'last_done_hours' => $hours];
            if ($plan->usesCalendar()) {
                $update['next_due_date'] = $completedDate->copy()->addDays((int) $plan->interval_days);
            }
            if ($plan->usesRunningHours()) {
                $update['next_due_hours'] = $hours + (float) $plan->interval_hours;
            }
            $plan->update($update);
            $plan->logHistory('completed', 'PM completed on ' . $completedDate->format('d M Y') . '. Next due: '
                . ($plan->next_due_date?->format('d M Y') ?? '-') . ' / ' . ($plan->next_due_hours ?? '-') . ' hrs');

            return $log;
        });
    }

    /** Called when a preventive work order is verified & closed. */
    public function completeFromWorkOrder(WorkOrder $workOrder): void
    {
        $schedule = $workOrder->pmSchedule;
        if (!$schedule || $schedule->status === 'completed') {
            return;
        }
        if ($workOrder->pmLog) {
            // Technician already filled the checklist; just close the schedule and roll plan
            $schedule->update(['status' => 'completed', 'completed_at' => now()]);
            return;
        }
        $plan = $schedule->plan;
        $this->recordCompletion($plan, [
            'completed_date' => ($workOrder->actual_end ?? now())->toDateString(),
            'actual_cost'    => $workOrder->actual_cost,
            'duration_hours' => $workOrder->labour_hours,
            'remarks'        => 'Auto-recorded from work order ' . $workOrder->wo_no,
        ], [], $schedule, $workOrder);
    }
}
