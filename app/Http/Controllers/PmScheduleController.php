<?php

namespace App\Http\Controllers;

use App\Models\PmLog;
use App\Models\PmSchedule;
use App\Services\AttachmentService;
use App\Services\PreventiveMaintenanceService;
use Illuminate\Http\Request;

class PmScheduleController extends Controller
{
    public function __construct(protected PreventiveMaintenanceService $service)
    {
    }

    public function index(Request $request)
    {
        $schedules = PmSchedule::with(['plan.asset', 'plan.technician', 'workOrder'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when(!$request->status, fn ($q) => $q->whereIn('status', ['due', 'in_progress']))
            ->orderBy('due_date')
            ->paginate(20)->withQueryString();

        return view('pm.schedules.index', compact('schedules'));
    }

    /** Run the PM due check manually (same code as the scheduled command). */
    public function runScheduler()
    {
        $count = $this->service->generateDueSchedules((int) setting('pm.pm_lookahead_days', 3));
        flash_toast('success', $count . ' PM work order(s) generated.');
        return back();
    }

    public function completeForm(PmSchedule $schedule)
    {
        $schedule->load(['plan.asset', 'plan.checklist.items', 'workOrder']);
        if ($schedule->status === 'completed') {
            flash_toast('info', 'This schedule is already completed.');
            return redirect()->route('pm.plans.show', $schedule->plan);
        }
        return view('pm.schedules.complete', compact('schedule'));
    }

    public function complete(Request $request, PmSchedule $schedule)
    {
        abort_if($schedule->status === 'completed', 403, 'Already completed.');

        $data = $request->validate([
            'completed_date'              => 'required|date',
            'running_hours_at_completion' => 'nullable|numeric|min:0',
            'duration_hours'              => 'nullable|numeric|min:0',
            'actual_cost'                 => 'nullable|numeric|min:0',
            'observations'                => 'nullable|string|max:3000',
            'remarks'                     => 'nullable|string|max:2000',
            'items'                       => 'nullable|array',
            'items.*.item_text'           => 'required|string|max:255',
            'items.*.result'              => 'required|in:ok,not_ok,na',
            'items.*.remarks'             => 'nullable|string|max:255',
            'attachments.*'               => 'nullable|' . AttachmentService::rule(),
        ]);

        $log = $this->service->recordCompletion($schedule->plan, $data, $data['items'] ?? [], $schedule, $schedule->workOrder, $request->file('attachments'));

        // Running hours entered here also update the asset
        if (!empty($data['running_hours_at_completion']) && $data['running_hours_at_completion'] > $schedule->plan->asset->current_running_hours) {
            $schedule->plan->asset->update(['current_running_hours' => $data['running_hours_at_completion']]);
        }

        // If the work order is still open, mark it completed for verification
        $wo = $schedule->workOrder;
        if ($wo && in_array($wo->status, ['open', 'assigned', 'in_progress', 'on_hold'])) {
            $wo->update([
                'status'               => 'verification_pending',
                'actual_start'         => $wo->actual_start ?? now(),
                'actual_end'           => now(),
                'actual_problem_found' => $wo->actual_problem_found ?? 'Preventive maintenance',
                'work_performed'       => 'PM checklist completed. ' . ($data['observations'] ?? ''),
                'actual_cost'          => $data['actual_cost'] ?? 0,
                'labour_hours'         => $data['duration_hours'] ?? 0,
            ]);
            $wo->logHistory('completed', 'PM checklist completed, awaiting verification', $wo->getOriginal('status'), 'verification_pending');
        }

        flash_toast('success', 'PM recorded. Next due: ' . show_date($schedule->plan->fresh()->next_due_date) . '.');
        return redirect()->route('pm.logs.show', $log);
    }

    public function logs(Request $request)
    {
        $logs = PmLog::with(['plan', 'asset', 'completer', 'workOrder'])
            ->when($request->asset_id, fn ($q, $v) => $q->where('asset_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('completed_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('completed_date', '<=', $v))
            ->latest('completed_date')->paginate(20)->withQueryString();

        return view('pm.logs.index', compact('logs'));
    }

    public function showLog(PmLog $log)
    {
        $log->load(['plan.checklist', 'asset', 'completer', 'items', 'workOrder', 'attachments.uploader']);
        return view('pm.logs.show', compact('log'));
    }
}
