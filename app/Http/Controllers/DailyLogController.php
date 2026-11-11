<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\DailyLog;
use App\Models\Department;
use App\Models\LogType;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\PlantLogService;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function __construct(protected PlantLogService $service)
    {
    }

    public function index(Request $request)
    {
        $logs = DailyLog::with(['logType', 'asset', 'shift', 'operator', 'department'])
            ->when($request->log_type_id, fn ($q, $v) => $q->where('log_type_id', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->asset_id, fn ($q, $v) => $q->where('asset_id', $v))
            ->when($request->shift_id, fn ($q, $v) => $q->where('shift_id', $v))
            ->when($request->abnormal, fn ($q) => $q->where('is_abnormal', true))
            ->when($request->from, fn ($q, $v) => $q->whereDate('log_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('log_date', '<=', $v))
            ->latest('log_date')->latest('id')->paginate(25)->withQueryString();

        return view('daily-logs.index', ['logs' => $logs] + $this->formData());
    }

    public function create(Request $request)
    {
        $logType = $request->log_type_id ? LogType::find($request->log_type_id) : null;
        return view('daily-logs.form', ['log' => new DailyLog(['log_date' => today(), 'log_type_id' => $logType?->id]), 'logType' => $logType] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $log = $this->service->create($data, $request->input('readings', []), $request->file('attachments'));
        flash_toast('success', 'Log ' . $log->log_no . ' saved.' . ($log->is_abnormal ? ' Flagged as abnormal.' : ''));
        return redirect()->route('daily-logs.show', $log);
    }

    public function show(DailyLog $dailyLog)
    {
        $dailyLog->load(['logType', 'asset', 'shift', 'operator', 'department', 'creator', 'attachments.uploader', 'history.user']);
        return view('daily-logs.show', ['log' => $dailyLog]);
    }

    public function edit(DailyLog $dailyLog)
    {
        $this->authorizeEdit($dailyLog);
        return view('daily-logs.form', ['log' => $dailyLog, 'logType' => $dailyLog->logType] + $this->formData());
    }

    public function update(Request $request, DailyLog $dailyLog)
    {
        $this->authorizeEdit($dailyLog);
        $data = $this->validated($request);
        unset($data['log_type_id']);
        $this->service->update($dailyLog, $data, $request->input('readings', []), $request->file('attachments'));
        flash_toast('success', 'Log updated (change recorded in audit).');
        return redirect()->route('daily-logs.show', $dailyLog);
    }

    /** Operators may edit only their own entries from the last 2 days; supervisors any. */
    protected function authorizeEdit(DailyLog $log): void
    {
        $user = auth()->user();
        if ($user->hasAnyPermission(['pm.settings.manage', 'pm.reports.view'])) {
            return;
        }
        if ($log->created_by !== $user->id || $log->created_at->lt(now()->subDays(2))) {
            abort(403, 'You can only edit your own log entries within 2 days.');
        }
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'log_type_id'          => 'required|exists:pm_log_types,id',
            'log_date'             => 'required|date',
            'department_id'        => 'nullable|exists:pm_departments,id',
            'asset_id'             => 'nullable|exists:pm_assets,id',
            'shift_id'             => 'nullable|exists:pm_shifts,id',
            'operator_id'          => 'nullable|exists:users,id',
            'opening_reading'      => 'nullable|numeric',
            'closing_reading'      => 'nullable|numeric|gte:opening_reading',
            'unit'                 => 'nullable|string|max:20',
            'observations'         => 'nullable|string|max:3000',
            'abnormal_conditions'  => 'nullable|string|max:3000',
            'remarks'              => 'nullable|string|max:2000',
            'update_running_hours' => 'boolean',
            'readings'             => 'nullable|array',
            'attachments.*'        => 'nullable|' . AttachmentService::rule(),
        ]);
    }

    protected function formData(): array
    {
        return [
            'logTypes'    => LogType::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'assets'      => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name', 'current_running_hours']),
            'shifts'      => Shift::where('is_active', true)->get(),
            'users'       => User::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
