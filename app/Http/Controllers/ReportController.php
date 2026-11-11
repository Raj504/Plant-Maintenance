<?php

namespace App\Http\Controllers;

use App\Models\AmcContract;
use App\Models\Asset;
use App\Models\Calibration;
use App\Models\CalibrationRecord;
use App\Models\CapacityLog;
use App\Models\Compliance;
use App\Models\Department;
use App\Models\MaintenanceRequest;
use App\Models\PmLog;
use App\Models\PmPlan;
use App\Models\PurchaseRequest;
use App\Models\Spare;
use App\Models\StockTransaction;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Every report is one small method named build<Something>() that returns
 *   ['columns' => ['key' => 'Label', ...], 'rows' => [[...], ...], 'totals' => [...optional]]
 * The same data feeds the HTML table, CSV export and PDF export.
 * Filters (from, to, department_id, asset_id) come from the query string.
 */
class ReportController extends Controller
{
    /** key => [title, group, description] */
    public const REPORTS = [
        'machine-capacity'          => ['Machine Capacity & Efficiency', 'Production', 'Rated vs actual output per machine run (e.g. 200 kg / 8 h rated, ran 5 h, made 100 kg = 80%)'],
        'request-register'          => ['Maintenance Request Register', 'Maintenance', 'All requests with status and cost'],
        'breakdown-register'        => ['Breakdown Register', 'Maintenance', 'Breakdown type requests only'],
        'work-order-register'       => ['Work Order Register', 'Maintenance', 'All work orders'],
        'pending-requests'          => ['Pending Maintenance Requests', 'Maintenance', 'Requests not yet closed'],
        'maintenance-history'       => ['Maintenance History by Machine', 'Maintenance', 'Closed work orders per asset'],
        'department-maintenance'    => ['Department-wise Maintenance', 'Maintenance', 'Requests, work orders and cost per department'],
        'technician-work'           => ['Technician-wise Work', 'Maintenance', 'Work orders and hours per technician'],
        'repair-cost'               => ['Repair Cost Report', 'Maintenance', 'Estimated vs actual cost per work order'],
        'labour-hours'              => ['Labour Hours Report', 'Maintenance', 'Labour entries'],
        'pm-schedule'               => ['PM Schedule', 'Preventive Maintenance', 'All active plans with next due'],
        'pm-due'                    => ['PM Due Report', 'Preventive Maintenance', 'Plans due within the look-ahead window'],
        'pm-overdue'                => ['PM Overdue Report', 'Preventive Maintenance', 'Plans past due'],
        'pm-completion'             => ['PM Completion Report', 'Preventive Maintenance', 'Completed PM logs'],
        'pm-compliance'             => ['PM Compliance %', 'Preventive Maintenance', 'On-time completion percentage per asset'],
        'pm-cost'                   => ['PM Cost Report', 'Preventive Maintenance', 'Cost of completed PM'],
        'downtime-register'         => ['Machine Downtime Register', 'Downtime', 'Downtime per closed work order'],
        'department-downtime'       => ['Department-wise Downtime', 'Downtime', 'Downtime hours per department'],
        'monthly-downtime'          => ['Monthly Downtime', 'Downtime', 'Downtime hours per month'],
        'breakdown-frequency'       => ['Breakdown Frequency', 'Downtime', 'Breakdown count per asset'],
        'repeat-failure'            => ['Repeat Failure Report', 'Downtime', 'Assets with the same problem category more than once'],
        'stock'                     => ['Spare Parts Stock', 'Inventory', 'Current stock and value'],
        'stock-ledger'              => ['Stock Ledger', 'Inventory', 'All stock movements'],
        'issue-register'            => ['Spare Parts Issue Register', 'Inventory', 'Issues against work orders'],
        'return-register'           => ['Spare Parts Return Register', 'Inventory', 'Returns against work orders'],
        'low-stock'                 => ['Low Stock Report', 'Inventory', 'Items at or below reorder level'],
        'consumption-by-machine'    => ['Spare Consumption by Machine', 'Inventory', 'Net spares consumed per asset'],
        'consumption-by-work-order' => ['Spare Consumption by Work Order', 'Inventory', 'Net spares consumed per work order'],
        'purchase-status'           => ['Purchase Requisition Status', 'Inventory', 'All requisitions'],
        'compliance-register'       => ['Statutory Compliance Register', 'Compliance', 'All compliance items'],
        'upcoming-inspection'       => ['Upcoming Inspections', 'Compliance', 'Compliance due within the window'],
        'overdue-inspection'        => ['Overdue Inspections', 'Compliance', 'Compliance past due'],
        'calibration-due'           => ['Calibration Due', 'Compliance', 'Instruments due soon or overdue'],
        'calibration-history'       => ['Calibration History', 'Compliance', 'All calibration records'],
        'amc-expiry'                => ['AMC Expiry Report', 'Compliance', 'Contracts expiring / expired'],
        'vendor-performance'        => ['Vendor Performance', 'Compliance', 'SLA rating per vendor'],
    ];

    public function index()
    {
        $grouped = collect(self::REPORTS)->map(fn ($r, $key) => ['key' => $key, 'title' => $r[0], 'group' => $r[1], 'description' => $r[2]])->groupBy('group');
        return view('reports.index', compact('grouped'));
    }

    public function show(Request $request, string $report)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);

        $filters = [
            'from'          => $request->input('from', now()->subMonths(3)->startOfMonth()->toDateString()),
            'to'            => $request->input('to', now()->toDateString()),
            'department_id' => $request->input('department_id'),
            'asset_id'      => $request->input('asset_id'),
        ];

        $method = 'build' . str_replace('-', '', ucwords($report, '-'));
        $data   = $this->$method($filters);
        $title  = self::REPORTS[$report][0];

        if ($request->export === 'csv') {
            return ReportExportService::csv($title, $data['columns'], $data['rows'], $data['totals'] ?? null);
        }
        if ($request->export === 'pdf') {
            return ReportExportService::pdf($title, $data['columns'], $data['rows'], $filters, $data['totals'] ?? null);
        }

        return view('reports.show', [
            'report'      => $report,
            'title'       => $title,
            'description' => self::REPORTS[$report][2],
            'columns'     => $data['columns'],
            'rows'        => $data['rows'],
            'totals'      => $data['totals'] ?? null,
            'filters'     => $filters,
            'departments' => Department::orderBy('name')->get(),
            'assets'      => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']),
        ]);
    }

    // ================================================================ helpers

    protected function range(array $f): array
    {
        return [$f['from'] . ' 00:00:00', $f['to'] . ' 23:59:59'];
    }

    protected function applyAssetDept($query, array $f, string $assetCol = 'asset_id', string $deptCol = 'department_id')
    {
        return $query->when($f['asset_id'], fn ($q, $v) => $q->where($assetCol, $v))
                     ->when($f['department_id'], fn ($q, $v) => $q->where($deptCol, $v));
    }

    // ================================================================ Production

    protected function buildMachineCapacity(array $f): array
    {
        $logs = CapacityLog::with(['asset', 'shift', 'operator'])
            ->whereBetween('log_date', [$f['from'], $f['to']])
            ->when($f['asset_id'], fn ($q, $v) => $q->where('asset_id', $v))
            ->when($f['department_id'], fn ($q, $v) => $q->whereHas('asset', fn ($a) => $a->where('department_id', $v)))
            ->orderBy('log_date')->get();

        $rows = $logs->map(fn ($l) => [
            'date'      => show_date($l->log_date),
            'asset'     => $l->asset->asset_code . ' ' . $l->asset->name,
            'shift'     => $l->shift?->name,
            'rated'     => $l->asset->rated_capacity ? ($l->asset->rated_capacity + 0) . ' ' . $l->asset->capacity_unit . ' / ' . ($l->asset->capacity_hours + 0) . ' h' : '-',
            'rated_hr'  => $l->asset->ratedPerHour(),
            'hours'     => $l->hours_run + 0,
            'expected'  => $l->expectedOutput(),
            'actual'    => $l->output_quantity + 0,
            'actual_hr' => $l->actualPerHour(),
            'efficiency'=> $l->efficiency() !== null ? $l->efficiency() . '%' : '-',
            'operator'  => $l->operator?->name,
        ])->all();

        // Summary per asset
        $summary = $logs->groupBy('asset_id')->map(function ($group) {
            $asset = $group->first()->asset;
            $hours = $group->sum('hours_run');
            $actual = $group->sum('output_quantity');
            $expected = $asset->ratedPerHour() ? $asset->ratedPerHour() * $hours : null;
            return [
                'date' => 'TOTAL', 'asset' => $asset->asset_code . ' ' . $asset->name, 'shift' => '', 'rated' => '', 'rated_hr' => $asset->ratedPerHour(),
                'hours' => round($hours, 2), 'expected' => $expected !== null ? round($expected, 2) : null, 'actual' => round($actual, 2),
                'actual_hr' => $hours > 0 ? round($actual / $hours, 2) : 0,
                'efficiency' => $expected ? round($actual / $expected * 100, 1) . '%' : '-', 'operator' => '',
            ];
        })->values()->all();

        return [
            'columns' => ['date' => 'Date', 'asset' => 'Machine', 'shift' => 'Shift', 'rated' => 'Rated Capacity', 'rated_hr' => 'Rated / hr', 'hours' => 'Hours Run', 'expected' => 'Expected Output', 'actual' => 'Actual Output', 'actual_hr' => 'Actual / hr', 'efficiency' => 'Efficiency', 'operator' => 'Operator'],
            'rows'    => $rows,
            'totals'  => $summary,
        ];
    }

    // ================================================================ Maintenance

    protected function requestRows($query): array
    {
        return $query->with(['asset', 'department', 'requester'])->orderBy('request_date')->get()->map(fn ($r) => [
            'no' => $r->request_no, 'date' => show_datetime($r->request_date), 'asset' => $r->asset->asset_code . ' ' . $r->asset->name,
            'department' => $r->department?->name, 'type' => status_label($r->request_type), 'priority' => ucfirst($r->priority),
            'problem' => \Illuminate\Support\Str::limit($r->problem_description, 80), 'requested_by' => $r->requester->name,
            'status' => status_label($r->status), 'estimated' => $r->estimated_cost, 'approved' => $r->approved_cost, 'closed' => show_date($r->closed_at),
        ])->all();
    }

    protected function requestColumns(): array
    {
        return ['no' => 'Request', 'date' => 'Date', 'asset' => 'Asset', 'department' => 'Department', 'type' => 'Type', 'priority' => 'Priority', 'problem' => 'Problem', 'requested_by' => 'Requested By', 'status' => 'Status', 'estimated' => 'Est. Cost', 'approved' => 'Approved Cost', 'closed' => 'Closed'];
    }

    protected function buildRequestRegister(array $f): array
    {
        $q = $this->applyAssetDept(MaintenanceRequest::whereBetween('request_date', $this->range($f)), $f);
        return ['columns' => $this->requestColumns(), 'rows' => $this->requestRows($q)];
    }

    protected function buildBreakdownRegister(array $f): array
    {
        $q = $this->applyAssetDept(MaintenanceRequest::where('request_type', 'breakdown')->whereBetween('request_date', $this->range($f)), $f);
        return ['columns' => $this->requestColumns(), 'rows' => $this->requestRows($q)];
    }

    protected function buildPendingRequests(array $f): array
    {
        $q = $this->applyAssetDept(MaintenanceRequest::whereNotIn('status', ['closed', 'cancelled', 'rejected']), $f);
        return ['columns' => $this->requestColumns(), 'rows' => $this->requestRows($q)];
    }

    protected function workOrderRows($query): array
    {
        return $query->with(['asset', 'department', 'technician', 'request'])->orderBy('created_at')->get()->map(fn ($w) => [
            'no' => $w->wo_no, 'request' => $w->request?->request_no ?? status_label($w->source), 'asset' => $w->asset->asset_code . ' ' . $w->asset->name,
            'department' => $w->department?->name, 'type' => status_label($w->work_type), 'priority' => ucfirst($w->priority), 'technician' => $w->technician?->name,
            'planned' => show_date($w->planned_start) . ' - ' . show_date($w->planned_end), 'actual' => show_date($w->actual_start) . ' - ' . show_date($w->actual_end),
            'status' => status_label($w->status), 'estimated' => $w->estimated_cost, 'actual_cost' => $w->actual_cost, 'labour' => $w->labour_hours, 'downtime' => $w->downtime_hours,
        ])->all();
    }

    protected function workOrderColumns(): array
    {
        return ['no' => 'WO', 'request' => 'Request', 'asset' => 'Asset', 'department' => 'Department', 'type' => 'Type', 'priority' => 'Priority', 'technician' => 'Technician', 'planned' => 'Planned', 'actual' => 'Actual', 'status' => 'Status', 'estimated' => 'Est. Cost', 'actual_cost' => 'Actual Cost', 'labour' => 'Labour Hrs', 'downtime' => 'Downtime Hrs'];
    }

    protected function buildWorkOrderRegister(array $f): array
    {
        $q = $this->applyAssetDept(WorkOrder::whereBetween('created_at', $this->range($f)), $f);
        return ['columns' => $this->workOrderColumns(), 'rows' => $this->workOrderRows($q)];
    }

    protected function buildMaintenanceHistory(array $f): array
    {
        $q = $this->applyAssetDept(WorkOrder::where('status', 'closed')->whereBetween('closed_at', $this->range($f)), $f)->orderBy('asset_id');
        return ['columns' => $this->workOrderColumns(), 'rows' => $this->workOrderRows($q)];
    }

    protected function buildRepairCost(array $f): array
    {
        $q = $this->applyAssetDept(WorkOrder::whereIn('status', ['closed', 'verification_pending', 'completed'])->whereBetween('created_at', $this->range($f)), $f);
        $rows = $this->workOrderRows($q);
        return ['columns' => $this->workOrderColumns(), 'rows' => $rows, 'totals' => [['no' => 'TOTAL', 'estimated' => array_sum(array_column($rows, 'estimated')), 'actual_cost' => array_sum(array_column($rows, 'actual_cost')), 'labour' => array_sum(array_column($rows, 'labour')), 'downtime' => array_sum(array_column($rows, 'downtime'))]]];
    }

    protected function buildDepartmentMaintenance(array $f): array
    {
        $rows = Department::orderBy('name')->get()->map(function ($d) use ($f) {
            $wo = WorkOrder::where('department_id', $d->id)->whereBetween('created_at', $this->range($f));
            return [
                'department' => $d->name,
                'requests'   => MaintenanceRequest::where('department_id', $d->id)->whereBetween('request_date', $this->range($f))->count(),
                'breakdowns' => MaintenanceRequest::where('department_id', $d->id)->where('request_type', 'breakdown')->whereBetween('request_date', $this->range($f))->count(),
                'work_orders'=> (clone $wo)->count(),
                'closed'     => (clone $wo)->where('status', 'closed')->count(),
                'cost'       => (clone $wo)->sum('actual_cost'),
                'downtime'   => (clone $wo)->sum('downtime_hours'),
            ];
        })->all();
        return ['columns' => ['department' => 'Department', 'requests' => 'Requests', 'breakdowns' => 'Breakdowns', 'work_orders' => 'Work Orders', 'closed' => 'Closed', 'cost' => 'Cost', 'downtime' => 'Downtime Hrs'], 'rows' => $rows];
    }

    protected function buildTechnicianWork(array $f): array
    {
        $rows = WorkOrder::with('technician')->whereNotNull('assigned_to')->whereBetween('created_at', $this->range($f))->get()
            ->groupBy('assigned_to')->map(fn ($g) => [
                'technician' => $g->first()->technician?->name, 'assigned' => $g->count(), 'closed' => $g->where('status', 'closed')->count(),
                'open' => $g->whereNotIn('status', ['closed', 'cancelled'])->count(), 'labour' => $g->sum('labour_hours'), 'cost' => $g->sum('actual_cost'),
            ])->values()->all();
        return ['columns' => ['technician' => 'Technician', 'assigned' => 'Assigned', 'closed' => 'Closed', 'open' => 'Open', 'labour' => 'Labour Hrs', 'cost' => 'Cost'], 'rows' => $rows];
    }

    protected function buildLabourHours(array $f): array
    {
        $rows = \App\Models\WorkOrderLabour::with(['user', 'workOrder.asset'])->whereBetween('work_date', [$f['from'], $f['to']])
            ->when($f['asset_id'], fn ($q, $v) => $q->whereHas('workOrder', fn ($w) => $w->where('asset_id', $v)))
            ->orderBy('work_date')->get()->map(fn ($l) => [
                'date' => show_date($l->work_date), 'person' => $l->user->name, 'wo' => $l->workOrder->wo_no, 'asset' => $l->workOrder->asset->name,
                'hours' => $l->hours, 'rate' => $l->rate, 'cost' => round($l->hours * $l->rate, 2), 'remarks' => $l->remarks,
            ])->all();
        return ['columns' => ['date' => 'Date', 'person' => 'Person', 'wo' => 'Work Order', 'asset' => 'Asset', 'hours' => 'Hours', 'rate' => 'Rate', 'cost' => 'Cost', 'remarks' => 'Remarks'], 'rows' => $rows,
            'totals' => [['date' => 'TOTAL', 'hours' => array_sum(array_column($rows, 'hours')), 'cost' => array_sum(array_column($rows, 'cost'))]]];
    }

    // ================================================================ Preventive

    protected function planRows($query): array
    {
        return $query->with(['asset', 'technician'])->orderBy('next_due_date')->get()->map(fn ($p) => [
            'plan' => $p->plan_no, 'name' => $p->name, 'asset' => $p->asset->asset_code . ' ' . $p->asset->name, 'trigger' => PmPlan::TRIGGERS[$p->trigger_type],
            'interval' => trim(($p->interval_days ? $p->interval_days . ' d' : '') . ' ' . ($p->interval_hours ? ($p->interval_hours + 0) . ' h' : '')),
            'last' => show_date($p->last_done_date), 'next_date' => show_date($p->next_due_date), 'next_hours' => $p->next_due_hours, 'current_hours' => $p->asset->current_running_hours,
            'technician' => $p->technician?->name, 'status' => $p->isOverdue() ? 'Overdue' : ($p->is_active ? 'Active' : 'Inactive'),
        ])->all();
    }

    protected function planColumns(): array
    {
        return ['plan' => 'Plan', 'name' => 'Name', 'asset' => 'Asset', 'trigger' => 'Trigger', 'interval' => 'Interval', 'last' => 'Last Done', 'next_date' => 'Next Due Date', 'next_hours' => 'Next Due Hrs', 'current_hours' => 'Current Hrs', 'technician' => 'Technician', 'status' => 'Status'];
    }

    protected function buildPmSchedule(array $f): array
    {
        return ['columns' => $this->planColumns(), 'rows' => $this->planRows($this->applyAssetDept(PmPlan::where('is_active', true), $f, 'asset_id', 'id')->when($f['department_id'], fn ($q, $v) => $q->whereHas('asset', fn ($a) => $a->where('department_id', $v))))];
    }

    protected function buildPmDue(array $f): array
    {
        $q = PmPlan::where('is_active', true)->whereBetween('next_due_date', [today(), today()->addDays((int) setting('pm.due_soon_days', 30))])->when($f['asset_id'], fn ($q, $v) => $q->where('asset_id', $v));
        return ['columns' => $this->planColumns(), 'rows' => $this->planRows($q)];
    }

    protected function buildPmOverdue(array $f): array
    {
        $q = PmPlan::with('asset')->where('is_active', true)->when($f['asset_id'], fn ($q, $v) => $q->where('asset_id', $v));
        $rows = collect($this->planRows($q))->where('status', 'Overdue')->values()->all();
        return ['columns' => $this->planColumns(), 'rows' => $rows];
    }

    protected function pmLogRows($query): array
    {
        return $query->with(['plan', 'asset', 'completer', 'items', 'schedule'])->orderBy('completed_date')->get()->map(fn ($l) => [
            'date' => show_date($l->completed_date), 'plan' => $l->plan->name, 'asset' => $l->asset->asset_code . ' ' . $l->asset->name, 'by' => $l->completer->name,
            'due' => show_date($l->schedule?->due_date), 'on_time' => $l->schedule?->due_date ? ($l->completed_date->lte($l->schedule->due_date->copy()->addDays(3)) ? 'Yes' : 'No') : '-',
            'hours' => $l->running_hours_at_completion, 'ok' => $l->items->where('result', 'ok')->count(), 'not_ok' => $l->items->where('result', 'not_ok')->count(),
            'duration' => $l->duration_hours, 'cost' => $l->actual_cost, 'remarks' => $l->remarks,
        ])->all();
    }

    protected function buildPmCompletion(array $f): array
    {
        $q = $this->applyAssetDept(PmLog::whereBetween('completed_date', [$f['from'], $f['to']]), $f, 'asset_id', 'id');
        return ['columns' => ['date' => 'Completed', 'plan' => 'Plan', 'asset' => 'Asset', 'by' => 'By', 'due' => 'Due Date', 'on_time' => 'On Time', 'hours' => 'Run Hrs', 'ok' => 'OK', 'not_ok' => 'Not OK', 'duration' => 'Duration', 'cost' => 'Cost', 'remarks' => 'Remarks'], 'rows' => $this->pmLogRows($q)];
    }

    protected function buildPmCost(array $f): array
    {
        $data = $this->buildPmCompletion($f);
        $data['totals'] = [['date' => 'TOTAL', 'duration' => array_sum(array_column($data['rows'], 'duration')), 'cost' => array_sum(array_column($data['rows'], 'cost'))]];
        return $data;
    }

    protected function buildPmCompliance(array $f): array
    {
        $logs = PmLog::with(['asset', 'schedule'])->whereBetween('completed_date', [$f['from'], $f['to']])->get();
        $overdueNow = PmPlan::with('asset')->where('is_active', true)->get()->filter->isOverdue()->groupBy('asset_id');
        $rows = $logs->groupBy('asset_id')->map(function ($g) use ($overdueNow) {
            $onTime = $g->filter(fn ($l) => $l->schedule?->due_date && $l->completed_date->lte($l->schedule->due_date->copy()->addDays(3)))->count();
            $asset = $g->first()->asset;
            return ['asset' => $asset->asset_code . ' ' . $asset->name, 'completed' => $g->count(), 'on_time' => $onTime, 'late' => $g->count() - $onTime,
                    'overdue_now' => $overdueNow->get($asset->id)?->count() ?? 0, 'compliance' => round($onTime / max(1, $g->count()) * 100, 1) . '%'];
        })->values()->all();
        return ['columns' => ['asset' => 'Asset', 'completed' => 'PM Completed', 'on_time' => 'On Time', 'late' => 'Late', 'overdue_now' => 'Overdue Now', 'compliance' => 'Compliance %'], 'rows' => $rows];
    }

    // ================================================================ Downtime

    protected function buildDowntimeRegister(array $f): array
    {
        $q = $this->applyAssetDept(WorkOrder::where('status', 'closed')->where('downtime_hours', '>', 0)->whereBetween('closed_at', $this->range($f)), $f);
        $rows = $this->workOrderRows($q);
        return ['columns' => $this->workOrderColumns(), 'rows' => $rows, 'totals' => [['no' => 'TOTAL', 'downtime' => array_sum(array_column($rows, 'downtime'))]]];
    }

    protected function buildDepartmentDowntime(array $f): array
    {
        $rows = WorkOrder::join('pm_departments', 'pm_departments.id', '=', 'pm_work_orders.department_id')->where('status', 'closed')->whereBetween('closed_at', $this->range($f))
            ->selectRaw('pm_departments.name as department, COUNT(*) as work_orders, SUM(downtime_hours) as downtime, SUM(actual_cost) as cost')->groupBy('pm_departments.name')->get()->map(fn ($r) => (array) $r)->all();
        return ['columns' => ['department' => 'Department', 'work_orders' => 'Work Orders', 'downtime' => 'Downtime Hrs', 'cost' => 'Cost'], 'rows' => $rows];
    }

    protected function buildMonthlyDowntime(array $f): array
    {
        $rows = $this->applyAssetDept(WorkOrder::where('status', 'closed')->whereBetween('closed_at', $this->range($f)), $f)
            ->selectRaw("DATE_FORMAT(closed_at, '%Y-%m') as month, COUNT(*) as work_orders, SUM(downtime_hours) as downtime, SUM(CASE WHEN work_type='breakdown' THEN 1 ELSE 0 END) as breakdowns, SUM(actual_cost) as cost")
            ->groupBy('month')->orderBy('month')->get()->map(fn ($r) => (array) $r)->all();
        return ['columns' => ['month' => 'Month', 'work_orders' => 'Work Orders', 'breakdowns' => 'Breakdowns', 'downtime' => 'Downtime Hrs', 'cost' => 'Cost'], 'rows' => $rows];
    }

    protected function buildBreakdownFrequency(array $f): array
    {
        $rows = MaintenanceRequest::join('pm_assets', 'pm_assets.id', '=', 'pm_requests.asset_id')->where('request_type', 'breakdown')->whereBetween('request_date', $this->range($f))
            ->when($f['department_id'], fn ($q, $v) => $q->where('pm_requests.department_id', $v))
            ->selectRaw('pm_assets.asset_code, pm_assets.name, COUNT(*) as breakdowns, SUM(machine_stopped) as stoppages, SUM(production_affected) as production_hits')
            ->groupBy('pm_assets.id', 'pm_assets.asset_code', 'pm_assets.name')->orderByDesc('breakdowns')->get()->map(fn ($r) => (array) $r)->all();
        return ['columns' => ['asset_code' => 'Code', 'name' => 'Asset', 'breakdowns' => 'Breakdowns', 'stoppages' => 'Machine Stopped', 'production_hits' => 'Production Affected'], 'rows' => $rows];
    }

    protected function buildRepeatFailure(array $f): array
    {
        $rows = MaintenanceRequest::join('pm_assets', 'pm_assets.id', '=', 'pm_requests.asset_id')->leftJoin('pm_categories', 'pm_categories.id', '=', 'pm_requests.problem_category_id')
            ->whereBetween('request_date', $this->range($f))
            ->selectRaw('pm_assets.asset_code, pm_assets.name, COALESCE(pm_categories.name, "Uncategorised") as problem, COUNT(*) as occurrences, MIN(request_date) as first_seen, MAX(request_date) as last_seen')
            ->groupBy('pm_assets.id', 'pm_assets.asset_code', 'pm_assets.name', 'problem')->having('occurrences', '>', 1)->orderByDesc('occurrences')->get()
            ->map(fn ($r) => ['asset_code' => $r->asset_code, 'name' => $r->name, 'problem' => $r->problem, 'occurrences' => $r->occurrences, 'first_seen' => show_date($r->first_seen), 'last_seen' => show_date($r->last_seen)])->all();
        return ['columns' => ['asset_code' => 'Code', 'name' => 'Asset', 'problem' => 'Problem Category', 'occurrences' => 'Occurrences', 'first_seen' => 'First', 'last_seen' => 'Last'], 'rows' => $rows];
    }

    // ================================================================ Inventory

    protected function buildStock(array $f): array
    {
        $rows = Spare::with('category')->where('is_active', true)->orderBy('name')->get()->map(fn ($s) => [
            'code' => $s->code, 'name' => $s->name, 'category' => $s->category?->name, 'uom' => $s->uom, 'stock' => $s->current_stock, 'min' => $s->min_stock, 'reorder' => $s->reorder_level,
            'unit_cost' => $s->unit_cost, 'value' => round($s->current_stock * $s->unit_cost, 2), 'location' => $s->store_location, 'low' => $s->isLowStock() ? 'YES' : '',
        ])->all();
        return ['columns' => ['code' => 'Code', 'name' => 'Spare', 'category' => 'Category', 'uom' => 'UoM', 'stock' => 'Stock', 'min' => 'Min', 'reorder' => 'Reorder', 'unit_cost' => 'Unit Cost', 'value' => 'Value', 'location' => 'Location', 'low' => 'Low'], 'rows' => $rows,
            'totals' => [['code' => 'TOTAL', 'value' => array_sum(array_column($rows, 'value'))]]];
    }

    protected function ledgerRows($query): array
    {
        return $query->with(['spare', 'workOrder', 'user'])->orderBy('id')->get()->map(fn ($t) => [
            'txn' => $t->txn_no, 'date' => show_date($t->transaction_date), 'spare' => $t->spare->code . ' ' . $t->spare->name, 'type' => StockTransaction::TYPES[$t->type],
            'in' => $t->isInward() ? $t->quantity : '', 'out' => !$t->isInward() ? $t->quantity : '', 'balance' => $t->balance_after, 'unit_cost' => $t->unit_cost, 'value' => round($t->quantity * $t->unit_cost, 2),
            'reference' => $t->workOrder?->wo_no ?? $t->reference_no, 'by' => $t->user->name, 'remarks' => $t->remarks,
        ])->all();
    }

    protected function ledgerColumns(): array
    {
        return ['txn' => 'Txn', 'date' => 'Date', 'spare' => 'Spare', 'type' => 'Type', 'in' => 'In', 'out' => 'Out', 'balance' => 'Balance', 'unit_cost' => 'Unit Cost', 'value' => 'Value', 'reference' => 'WO / Ref', 'by' => 'By', 'remarks' => 'Remarks'];
    }

    protected function buildStockLedger(array $f): array
    {
        return ['columns' => $this->ledgerColumns(), 'rows' => $this->ledgerRows(StockTransaction::whereBetween('transaction_date', [$f['from'], $f['to']]))];
    }

    protected function buildIssueRegister(array $f): array
    {
        return ['columns' => $this->ledgerColumns(), 'rows' => $this->ledgerRows(StockTransaction::where('type', 'issue')->whereBetween('transaction_date', [$f['from'], $f['to']]))];
    }

    protected function buildReturnRegister(array $f): array
    {
        return ['columns' => $this->ledgerColumns(), 'rows' => $this->ledgerRows(StockTransaction::where('type', 'return')->whereBetween('transaction_date', [$f['from'], $f['to']]))];
    }

    protected function buildLowStock(array $f): array
    {
        $data = $this->buildStock($f);
        $data['rows'] = array_values(array_filter($data['rows'], fn ($r) => $r['low'] === 'YES'));
        unset($data['totals']);
        return $data;
    }

    protected function buildConsumptionByMachine(array $f): array
    {
        $rows = StockTransaction::join('pm_work_orders', 'pm_work_orders.id', '=', 'pm_stock_transactions.work_order_id')
            ->join('pm_assets', 'pm_assets.id', '=', 'pm_work_orders.asset_id')->join('pm_spares', 'pm_spares.id', '=', 'pm_stock_transactions.spare_id')
            ->whereIn('type', ['issue', 'return'])->whereBetween('transaction_date', [$f['from'], $f['to']])
            ->when($f['asset_id'], fn ($q, $v) => $q->where('pm_assets.id', $v))
            ->selectRaw("pm_assets.asset_code, pm_assets.name as asset, pm_spares.code as spare_code, pm_spares.name as spare, SUM(CASE WHEN type='issue' THEN quantity ELSE -quantity END) as qty, SUM(CASE WHEN type='issue' THEN quantity*pm_stock_transactions.unit_cost ELSE -quantity*pm_stock_transactions.unit_cost END) as value")
            ->groupBy('pm_assets.asset_code', 'pm_assets.name', 'pm_spares.code', 'pm_spares.name')->orderBy('pm_assets.asset_code')->get()->map(fn ($r) => (array) $r)->all();
        return ['columns' => ['asset_code' => 'Asset Code', 'asset' => 'Asset', 'spare_code' => 'Spare Code', 'spare' => 'Spare', 'qty' => 'Net Qty', 'value' => 'Value'], 'rows' => $rows];
    }

    protected function buildConsumptionByWorkOrder(array $f): array
    {
        $rows = StockTransaction::join('pm_work_orders', 'pm_work_orders.id', '=', 'pm_stock_transactions.work_order_id')->join('pm_spares', 'pm_spares.id', '=', 'pm_stock_transactions.spare_id')
            ->whereIn('type', ['issue', 'return'])->whereBetween('transaction_date', [$f['from'], $f['to']])
            ->selectRaw("pm_work_orders.wo_no, pm_spares.code as spare_code, pm_spares.name as spare, SUM(CASE WHEN type='issue' THEN quantity ELSE -quantity END) as qty, SUM(CASE WHEN type='issue' THEN quantity*pm_stock_transactions.unit_cost ELSE -quantity*pm_stock_transactions.unit_cost END) as value")
            ->groupBy('pm_work_orders.wo_no', 'pm_spares.code', 'pm_spares.name')->orderBy('pm_work_orders.wo_no')->get()->map(fn ($r) => (array) $r)->all();
        return ['columns' => ['wo_no' => 'Work Order', 'spare_code' => 'Spare Code', 'spare' => 'Spare', 'qty' => 'Net Qty', 'value' => 'Value'], 'rows' => $rows];
    }

    protected function buildPurchaseStatus(array $f): array
    {
        $rows = PurchaseRequest::with(['spare', 'requester', 'vendor', 'workOrder'])->whereBetween('created_at', $this->range($f))->orderBy('created_at')->get()->map(fn ($p) => [
            'pr' => $p->pr_no, 'date' => show_date($p->created_at), 'spare' => $p->spare->code . ' ' . $p->spare->name, 'required' => $p->required_qty, 'available' => $p->available_stock, 'shortage' => $p->shortage_qty,
            'required_by' => show_date($p->required_by_date), 'wo' => $p->workOrder?->wo_no, 'vendor' => $p->vendor?->name, 'by' => $p->requester->name, 'status' => status_label($p->status),
        ])->all();
        return ['columns' => ['pr' => 'PR', 'date' => 'Date', 'spare' => 'Spare', 'required' => 'Required', 'available' => 'Available', 'shortage' => 'Shortage', 'required_by' => 'Required By', 'wo' => 'WO', 'vendor' => 'Vendor', 'by' => 'Raised By', 'status' => 'Status'], 'rows' => $rows];
    }

    // ================================================================ Compliance

    protected function complianceRows($query): array
    {
        return $query->with(['asset', 'department'])->orderBy('next_due_date')->get()->map(fn ($c) => [
            'no' => $c->compliance_no, 'type' => $c->compliance_type, 'name' => $c->inspection_name, 'asset' => $c->asset?->name, 'department' => $c->department?->name, 'authority' => $c->authority, 'agency' => $c->agency,
            'last' => show_date($c->last_inspection_date), 'next' => show_date($c->next_due_date), 'certificate' => $c->certificate_no, 'result' => ucfirst((string) $c->result), 'status' => status_label($c->status),
        ])->all();
    }

    protected function complianceColumns(): array
    {
        return ['no' => 'No.', 'type' => 'Type', 'name' => 'Inspection', 'asset' => 'Asset', 'department' => 'Department', 'authority' => 'Authority', 'agency' => 'Agency', 'last' => 'Last', 'next' => 'Next Due', 'certificate' => 'Certificate', 'result' => 'Result', 'status' => 'Status'];
    }

    protected function buildComplianceRegister(array $f): array
    {
        return ['columns' => $this->complianceColumns(), 'rows' => $this->complianceRows($this->applyAssetDept(Compliance::query(), $f))];
    }

    protected function buildUpcomingInspection(array $f): array
    {
        return ['columns' => $this->complianceColumns(), 'rows' => $this->complianceRows($this->applyAssetDept(Compliance::whereBetween('next_due_date', [today(), today()->addDays((int) setting('pm.due_soon_days', 30))]), $f))];
    }

    protected function buildOverdueInspection(array $f): array
    {
        return ['columns' => $this->complianceColumns(), 'rows' => $this->complianceRows($this->applyAssetDept(Compliance::whereDate('next_due_date', '<', today()), $f))];
    }

    protected function buildCalibrationDue(array $f): array
    {
        $rows = $this->applyAssetDept(Calibration::whereIn('status', ['due_soon', 'overdue']), $f)->with(['asset', 'department'])->orderBy('next_due_date')->get()->map(fn ($c) => [
            'code' => $c->instrument_code, 'name' => $c->instrument_name, 'asset' => $c->asset?->name, 'department' => $c->department?->name, 'serial' => $c->serial_number, 'agency' => $c->agency,
            'last' => show_date($c->last_calibration_date), 'next' => show_date($c->next_due_date), 'certificate' => $c->certificate_no, 'status' => status_label($c->status),
        ])->all();
        return ['columns' => ['code' => 'Code', 'name' => 'Instrument', 'asset' => 'Asset', 'department' => 'Department', 'serial' => 'Serial', 'agency' => 'Agency', 'last' => 'Last', 'next' => 'Next Due', 'certificate' => 'Certificate', 'status' => 'Status'], 'rows' => $rows];
    }

    protected function buildCalibrationHistory(array $f): array
    {
        $rows = CalibrationRecord::with(['calibration', 'recorder'])->whereBetween('calibration_date', [$f['from'], $f['to']])->orderBy('calibration_date')->get()->map(fn ($r) => [
            'date' => show_date($r->calibration_date), 'code' => $r->calibration->instrument_code, 'name' => $r->calibration->instrument_name, 'agency' => $r->agency, 'result' => ucfirst((string) $r->result), 'certificate' => $r->certificate_no, 'next' => show_date($r->next_due_date), 'by' => $r->recorder?->name,
        ])->all();
        return ['columns' => ['date' => 'Date', 'code' => 'Code', 'name' => 'Instrument', 'agency' => 'Agency', 'result' => 'Result', 'certificate' => 'Certificate', 'next' => 'Next Due', 'by' => 'By'], 'rows' => $rows];
    }

    protected function buildAmcExpiry(array $f): array
    {
        $rows = AmcContract::with(['vendor', 'assets'])->orderBy('end_date')->get()->map(fn ($a) => [
            'amc' => $a->amc_no, 'name' => $a->name, 'vendor' => $a->vendor->name, 'assets' => $a->assets->pluck('asset_code')->join(', '), 'start' => show_date($a->start_date), 'end' => show_date($a->end_date),
            'days_left' => $a->daysToExpiry(), 'value' => $a->contract_value, 'status' => ucfirst($a->status),
        ])->all();
        return ['columns' => ['amc' => 'AMC', 'name' => 'Contract', 'vendor' => 'Vendor', 'assets' => 'Assets', 'start' => 'Start', 'end' => 'End', 'days_left' => 'Days Left', 'value' => 'Value', 'status' => 'Status'], 'rows' => $rows];
    }

    protected function buildVendorPerformance(array $f): array
    {
        $rows = Vendor::with('serviceRecords')->orderBy('name')->get()->map(function ($v) {
            $s = $v->serviceRecords; $n = max(1, $s->count());
            return ['code' => $v->code, 'vendor' => $v->name, 'services' => $s->count(), 'response' => round($s->where('response_within_sla', true)->count() / $n * 100) . '%', 'resolution' => round($s->where('resolution_within_sla', true)->count() / $n * 100) . '%',
                    'quality' => round($s->avg('work_quality') ?? 0, 1) . ' / 5', 'attendance' => round($s->where('attended', true)->count() / $n * 100) . '%', 'repeat' => $s->where('repeat_failure', true)->count(), 'cost' => $s->sum('cost'), 'rating' => $v->slaRating() !== null ? $v->slaRating() . '%' : '-'];
        })->all();
        return ['columns' => ['code' => 'Code', 'vendor' => 'Vendor', 'services' => 'Services', 'response' => 'Response SLA', 'resolution' => 'Resolution SLA', 'quality' => 'Quality', 'attendance' => 'Attendance', 'repeat' => 'Repeat Failures', 'cost' => 'Total Cost', 'rating' => 'SLA Rating'], 'rows' => $rows];
    }
}
