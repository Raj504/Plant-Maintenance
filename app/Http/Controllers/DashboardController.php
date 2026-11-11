<?php

namespace App\Http\Controllers;

use App\Models\AmcContract;
use App\Models\Asset;
use App\Models\Calibration;
use App\Models\Compliance;
use App\Models\Department;
use App\Models\MaintenanceRequest;
use App\Models\Plant;
use App\Models\PmPlan;
use App\Models\Spare;
use App\Models\StockTransaction;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subMonths(6)->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());
        $departmentId = $request->input('department_id');
        $plantId      = $request->input('plant_id');

        // Base queries with optional department / plant filter
        $assetFilter = function ($q) use ($departmentId, $plantId) {
            if ($departmentId) {
                $q->where('department_id', $departmentId);
            }
            if ($plantId) {
                $q->where('plant_id', $plantId);
            }
        };
        $assetIds = Asset::where($assetFilter)->pluck('id');
        $byAsset  = fn ($q) => ($departmentId || $plantId) ? $q->whereIn('asset_id', $assetIds) : $q;

        $dueSoon = (int) setting('pm.due_soon_days', 30);

        $cards = [
            'total_assets'       => Asset::where($assetFilter)->count(),
            'active_assets'      => Asset::where($assetFilter)->where('status', 'active')->count(),
            'breakdown_assets'   => Asset::where($assetFilter)->where('status', 'breakdown')->count(),
            'open_requests'      => $byAsset(MaintenanceRequest::query())->whereNotIn('status', ['closed', 'cancelled', 'rejected'])->count(),
            'pending_inspection' => $byAsset(MaintenanceRequest::query())->whereIn('status', ['submitted', 'inspection_pending'])->count(),
            'pending_assessment' => $byAsset(MaintenanceRequest::query())->where('status', 'assessment_pending')->count(),
            'pending_approval'   => $byAsset(MaintenanceRequest::query())->where('status', 'approval_pending')->count(),
            'open_work_orders'   => $byAsset(WorkOrder::query())->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'overdue_work_orders'=> $byAsset(WorkOrder::query())->whereNotIn('status', ['closed', 'cancelled'])->whereDate('planned_end', '<', today())->count(),
            'pm_due'             => $byAsset(PmPlan::query())->where('is_active', true)->whereBetween('next_due_date', [today(), today()->addDays(7)])->count(),
            'pm_overdue'         => $byAsset(PmPlan::query())->where('is_active', true)->whereDate('next_due_date', '<', today())->count(),
            'low_stock'          => Spare::where('is_active', true)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
            'amc_expiring'       => AmcContract::where('status', 'active')->whereBetween('end_date', [today(), today()->addDays($dueSoon)])->count(),
            'compliance_due'     => $byAsset(Compliance::query())->whereIn('status', ['due_soon', 'overdue'])->count(),
            'calibration_overdue'=> $byAsset(Calibration::query())->where('status', 'overdue')->count(),
        ];

        // ---------------- Charts
        $months = collect();
        $cursor = \Carbon\Carbon::parse($from)->startOfMonth();
        $end    = \Carbon\Carbon::parse($to)->endOfMonth();
        while ($cursor->lte($end)) {
            $months->push($cursor->format('Y-m'));
            $cursor->addMonth();
        }

        $breakdowns = $byAsset(MaintenanceRequest::query())->where('request_type', 'breakdown')
            ->whereBetween('request_date', [$from, $to . ' 23:59:59'])
            ->selectRaw("DATE_FORMAT(request_date, '%Y-%m') as ym, COUNT(*) as total")->groupBy('ym')->pluck('total', 'ym');

        $costs = $byAsset(WorkOrder::query())->where('status', 'closed')
            ->whereBetween('closed_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("DATE_FORMAT(closed_at, '%Y-%m') as ym, SUM(actual_cost) as total")->groupBy('ym')->pluck('total', 'ym');

        $monthlyChart = [
            'labels'     => $months->map(fn ($m) => \Carbon\Carbon::parse($m . '-01')->format('M y'))->values(),
            'breakdowns' => $months->map(fn ($m) => (int) ($breakdowns[$m] ?? 0))->values(),
            'costs'      => $months->map(fn ($m) => (float) ($costs[$m] ?? 0))->values(),
        ];

        $deptRequests = MaintenanceRequest::join('pm_departments', 'pm_departments.id', '=', 'pm_requests.department_id')
            ->whereBetween('request_date', [$from, $to . ' 23:59:59'])
            ->selectRaw('pm_departments.name as name, COUNT(*) as total')->groupBy('pm_departments.name')->pluck('total', 'name');

        $downtime = $byAsset(WorkOrder::query())->join('pm_assets', 'pm_assets.id', '=', 'pm_work_orders.asset_id')
            ->where('pm_work_orders.status', 'closed')->whereBetween('closed_at', [$from, $to . ' 23:59:59'])
            ->selectRaw('pm_assets.name as name, SUM(downtime_hours) as total')->groupBy('pm_assets.name')
            ->orderByDesc('total')->limit(8)->pluck('total', 'name');

        $pmVsCorrective = [
            'Preventive' => $byAsset(WorkOrder::query())->where('work_type', 'preventive')->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count(),
            'Corrective / Breakdown' => $byAsset(WorkOrder::query())->where('work_type', '!=', 'preventive')->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count(),
        ];

        $woStatus = $byAsset(WorkOrder::query())->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')
            ->mapWithKeys(fn ($v, $k) => [status_label($k) => $v]);

        $spareConsumption = StockTransaction::join('pm_spares', 'pm_spares.id', '=', 'pm_stock_transactions.spare_id')
            ->where('type', 'issue')->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('pm_spares.name as name, SUM(quantity) as total')->groupBy('pm_spares.name')
            ->orderByDesc('total')->limit(8)->pluck('total', 'name');

        // ---------------- Lists
        $criticalRequests = $byAsset(MaintenanceRequest::query())->with('asset')->where('priority', 'critical')
            ->whereNotIn('status', ['closed', 'cancelled', 'rejected'])->latest('request_date')->limit(6)->get();
        $overdueWorkOrders = $byAsset(WorkOrder::query())->with(['asset', 'technician'])->whereNotIn('status', ['closed', 'cancelled'])
            ->whereDate('planned_end', '<', today())->orderBy('planned_end')->limit(6)->get();
        $upcomingPm = $byAsset(PmPlan::query())->with('asset')->where('is_active', true)->whereNotNull('next_due_date')
            ->orderBy('next_due_date')->limit(6)->get();
        $overdueCompliance = $byAsset(Compliance::query())->with('asset')->whereIn('status', ['overdue', 'due_soon'])->orderBy('next_due_date')->limit(6)->get();

        return view('dashboard', compact(
            'cards', 'monthlyChart', 'deptRequests', 'downtime', 'pmVsCorrective', 'woStatus', 'spareConsumption',
            'criticalRequests', 'overdueWorkOrders', 'upcomingPm', 'overdueCompliance', 'from', 'to', 'departmentId', 'plantId'
        ))->with([
            'departments' => Department::orderBy('name')->get(),
            'plants'      => Plant::orderBy('name')->get(),
        ]);
    }
}
