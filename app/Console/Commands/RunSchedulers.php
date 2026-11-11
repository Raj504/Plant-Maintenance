<?php

namespace App\Console\Commands;

use App\Models\AmcContract;
use App\Models\Spare;
use App\Models\WorkOrder;
use App\Services\ComplianceService;
use App\Services\NotificationService;
use App\Services\PreventiveMaintenanceService;
use App\Services\StockLedgerService;
use Illuminate\Console\Command;

/**
 * php artisan pm:run-schedulers
 *
 * Runs every automated check. Scheduled daily in routes/console.php.
 * Every alert uses a dedupe key, so running it twice never creates duplicate
 * notifications, schedules or work orders.
 */
class RunSchedulers extends Command
{
    protected $signature = 'pm:run-schedulers {--only= : pm|workorders|stock|amc|compliance}';
    protected $description = 'Generate due PM work orders and send overdue / expiry / low-stock reminders';

    public function handle(PreventiveMaintenanceService $pm, ComplianceService $compliance, StockLedgerService $ledger): int
    {
        $only = $this->option('only');
        $week = now()->format('YW');

        // 1. Preventive maintenance: due by calendar date or running hours
        if (!$only || $only === 'pm') {
            $created = $pm->generateDueSchedules((int) setting('pm.pm_lookahead_days', 3));
            $this->info("PM: {$created} work order(s) generated.");

            \App\Models\PmPlan::with('asset')->where('is_active', true)->get()->filter->isOverdue()->each(function ($plan) use ($week) {
                NotificationService::notifyPermission('pm.pm.manage', 'pm_overdue', 'PM overdue: ' . $plan->name, $plan->asset->name . ' due ' . show_date($plan->next_due_date), route('pm.plans.show', $plan), 'danger', 'pm_overdue:' . $plan->id . ':' . $week);
            });
        }

        // 2. Overdue work orders
        if (!$only || $only === 'workorders') {
            $overdue = WorkOrder::with(['asset', 'technician'])->whereNotIn('status', ['closed', 'cancelled'])->whereDate('planned_end', '<', today())->get();
            foreach ($overdue as $wo) {
                $key = 'wo_overdue:' . $wo->id . ':' . now()->format('Ymd');
                NotificationService::notifyUser($wo->assigned_to, 'work_order_overdue', 'Work order ' . $wo->wo_no . ' is overdue', $wo->asset->name . ' planned end ' . show_date($wo->planned_end), route('work-orders.show', $wo), 'danger', $key);
                NotificationService::notifyPermission('pm.workorders.assign', 'work_order_overdue', 'Work order ' . $wo->wo_no . ' is overdue', $wo->asset->name, route('work-orders.show', $wo), 'danger', $key);
            }
            $this->info('Work orders: ' . $overdue->count() . ' overdue.');
        }

        // 3. Low stock (also re-syncs the cached stock from the ledger)
        if (!$only || $only === 'stock') {
            $ledger->recalculate();
            $low = Spare::where('is_active', true)->whereColumn('current_stock', '<=', 'reorder_level')->get();
            foreach ($low as $spare) {
                NotificationService::notifyPermission('pm.stock.view', 'low_stock', 'Low stock: ' . $spare->name, 'Stock ' . ($spare->current_stock + 0) . ' ' . $spare->uom . ' (reorder ' . ($spare->reorder_level + 0) . ')', route('spares.show', $spare), 'warning', 'low_stock:' . $spare->id . ':' . $week);
            }
            $this->info('Stock: ' . $low->count() . ' low-stock item(s).');
        }

        // 4. AMC expiry and missed visits
        if (!$only || $only === 'amc') {
            $days = (int) setting('pm.amc_expiry_alert_days', 30);
            AmcContract::where('status', 'active')->whereDate('end_date', '<', today())->update(['status' => 'expired']);
            $expiring = AmcContract::with('vendor')->where('status', 'active')->whereBetween('end_date', [today(), today()->addDays($days)])->get();
            foreach ($expiring as $amc) {
                NotificationService::notifyPermission('pm.amc.view', 'amc_expiry', 'AMC ' . $amc->amc_no . ' expires in ' . $amc->daysToExpiry() . ' days', $amc->vendor->name . ' - ' . $amc->name, route('amc.show', $amc), 'warning', 'amc_expiry:' . $amc->id . ':' . $week);
            }
            $missed = \App\Models\AmcVisit::with('contract')->where('status', 'scheduled')->whereDate('scheduled_date', '<', today())->get();
            foreach ($missed as $visit) {
                NotificationService::notifyPermission('pm.amc.manage', 'amc_visit_missed', 'Vendor visit missed: ' . $visit->contract->name, 'Scheduled ' . show_date($visit->scheduled_date), route('amc.show', $visit->contract), 'warning', 'amc_visit:' . $visit->id . ':' . $week);
            }
            $this->info('AMC: ' . $expiring->count() . ' expiring, ' . $missed->count() . ' missed visit(s).');
        }

        // 5. Statutory compliance & calibration
        if (!$only || $only === 'compliance') {
            $compliance->refreshAllStatuses();
            $this->info('Compliance & calibration statuses refreshed.');
        }

        return self::SUCCESS;
    }
}
