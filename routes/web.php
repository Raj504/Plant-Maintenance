<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\LogTypeController;
use App\Http\Controllers\Admin\PlantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AmcController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CalibrationController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PmPlanController;
use App\Http\Controllers\PmScheduleController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SafetyPermitController;
use App\Http\Controllers\ShiftHandoverController;
use App\Http\Controllers\SpareController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plant Maintenance & Operations – routes
|--------------------------------------------------------------------------
| Every module route sits inside the "auth" group and declares the
| permission it needs with ->middleware('permission:...'). That is the
| server-side check; the sidebar only hides links as a convenience.
*/

// ------------------------------------------------------------------ Auth
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');
});
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:pm.dashboard.view');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Notifications (every logged in user)
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Attachments (owner check inside the controller)
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // ------------------------------------------------------------------ Assets
    Route::middleware('permission:pm.assets.view')->group(function () {
        Route::get('assets', [AssetController::class, 'index'])->name('assets.index');
        Route::get('assets/tree', [AssetController::class, 'tree'])->name('assets.tree');
        Route::get('assets/scan/{code}', [AssetController::class, 'scan'])->name('assets.scan');
    });
    Route::middleware('permission:pm.assets.create')->group(function () {
        Route::get('assets/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
    });
    Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show')->middleware('permission:pm.assets.view');
    Route::middleware('permission:pm.assets.edit')->group(function () {
        Route::get('assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::post('assets/{asset}/documents', [AssetController::class, 'storeDocument'])->name('assets.documents.store');
    });
    Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy')->middleware('permission:pm.assets.delete');
    Route::middleware('permission:pm.assets.readings')->group(function () {
        Route::post('assets/{asset}/readings', [AssetController::class, 'storeReading'])->name('assets.readings.store');
        Route::post('assets/{asset}/capacity-logs', [AssetController::class, 'storeCapacityLog'])->name('assets.capacity.store');
    });
    Route::middleware('permission:pm.stock.manage')->group(function () {
        Route::post('assets/{asset}/bom', [AssetController::class, 'storeBom'])->name('assets.bom.store');
        Route::delete('assets/{asset}/bom/{bom}', [AssetController::class, 'destroyBom'])->name('assets.bom.destroy');
    });

    // ------------------------------------------------------------------ Maintenance requests
    Route::middleware('permission:pm.requests.view')->group(function () {
        Route::get('requests', [MaintenanceRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->name('requests.show');
    });
    Route::middleware('permission:pm.requests.create')->group(function () {
        Route::get('requests/create', [MaintenanceRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [MaintenanceRequestController::class, 'store'])->name('requests.store');
    });
    Route::middleware('permission:pm.requests.create|pm.requests.manage')->group(function () {
        Route::get('requests/{maintenanceRequest}/edit', [MaintenanceRequestController::class, 'edit'])->name('requests.edit');
        Route::put('requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'update'])->name('requests.update');
        Route::post('requests/{maintenanceRequest}/resubmit', [MaintenanceRequestController::class, 'resubmit'])->name('requests.resubmit');
        Route::post('requests/{maintenanceRequest}/cancel', [MaintenanceRequestController::class, 'cancel'])->name('requests.cancel');
    });
    Route::post('requests/{maintenanceRequest}/inspection', [MaintenanceRequestController::class, 'storeInspection'])->name('requests.inspection')->middleware('permission:pm.requests.inspect');
    Route::post('requests/{maintenanceRequest}/assessment', [MaintenanceRequestController::class, 'storeAssessment'])->name('requests.assessment')->middleware('permission:pm.requests.assess');
    Route::post('requests/{maintenanceRequest}/approval', [MaintenanceRequestController::class, 'storeApproval'])->name('requests.approval')->middleware('permission:pm.requests.approve');
    Route::middleware('permission:pm.requests.emergency')->group(function () {
        Route::post('requests/{maintenanceRequest}/emergency', [MaintenanceRequestController::class, 'emergency'])->name('requests.emergency');
        Route::post('requests/{maintenanceRequest}/emergency-review', [MaintenanceRequestController::class, 'emergencyReview'])->name('requests.emergency-review');
    });

    // ------------------------------------------------------------------ Work orders
    Route::middleware('permission:pm.workorders.view')->group(function () {
        Route::get('work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::get('work-orders/{workOrder}/print', [WorkOrderController::class, 'print'])->name('work-orders.print');
    });
    Route::middleware('permission:pm.workorders.create')->group(function () {
        Route::get('work-orders/create', [WorkOrderController::class, 'create'])->name('work-orders.create');
        Route::post('work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    });
    Route::post('work-orders/{workOrder}/assign', [WorkOrderController::class, 'assign'])->name('work-orders.assign')->middleware('permission:pm.workorders.assign');
    Route::middleware('permission:pm.workorders.execute')->group(function () {
        Route::post('work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');
        Route::post('work-orders/{workOrder}/hold', [WorkOrderController::class, 'hold'])->name('work-orders.hold');
        Route::post('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
        Route::post('work-orders/{workOrder}/tasks', [WorkOrderController::class, 'storeTask'])->name('work-orders.tasks.store');
        Route::post('work-orders/{workOrder}/tasks/{task}/toggle', [WorkOrderController::class, 'toggleTask'])->name('work-orders.tasks.toggle');
        Route::post('work-orders/{workOrder}/labour', [WorkOrderController::class, 'storeLabour'])->name('work-orders.labour.store');
        Route::post('work-orders/{workOrder}/photos', [WorkOrderController::class, 'storePhotos'])->name('work-orders.photos.store');
    });
    Route::post('work-orders/{workOrder}/verify', [WorkOrderController::class, 'verify'])->name('work-orders.verify')->middleware('permission:pm.workorders.verify');
    Route::post('work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel')->middleware('permission:pm.workorders.cancel');
    Route::post('work-orders/{workOrder}/vendor-service', [WorkOrderController::class, 'storeVendorService'])->name('work-orders.vendor-service.store')->middleware('permission:pm.workorders.execute|pm.amc.manage');

    // ------------------------------------------------------------------ Preventive maintenance
    Route::middleware('permission:pm.pm.view')->group(function () {
        Route::get('pm/plans', [PmPlanController::class, 'index'])->name('pm.plans.index');
        Route::get('pm/plans/{plan}', [PmPlanController::class, 'show'])->name('pm.plans.show');
        Route::get('pm/schedules', [PmScheduleController::class, 'index'])->name('pm.schedules.index');
        Route::get('pm/logs', [PmScheduleController::class, 'logs'])->name('pm.logs.index');
        Route::get('pm/logs/{log}', [PmScheduleController::class, 'showLog'])->name('pm.logs.show');
        Route::get('pm/checklists', [ChecklistController::class, 'index'])->name('pm.checklists.index');
    });
    Route::middleware('permission:pm.pm.manage')->group(function () {
        Route::get('pm/plans/create', [PmPlanController::class, 'create'])->name('pm.plans.create');
        Route::post('pm/plans', [PmPlanController::class, 'store'])->name('pm.plans.store');
        Route::get('pm/plans/{plan}/edit', [PmPlanController::class, 'edit'])->name('pm.plans.edit');
        Route::put('pm/plans/{plan}', [PmPlanController::class, 'update'])->name('pm.plans.update');
        Route::post('pm/plans/{plan}/generate', [PmPlanController::class, 'generate'])->name('pm.plans.generate');
        Route::post('pm/plans/{plan}/toggle', [PmPlanController::class, 'toggle'])->name('pm.plans.toggle');
        Route::post('pm/run-scheduler', [PmScheduleController::class, 'runScheduler'])->name('pm.run-scheduler');
        Route::get('pm/checklists/create', [ChecklistController::class, 'create'])->name('pm.checklists.create');
        Route::post('pm/checklists', [ChecklistController::class, 'store'])->name('pm.checklists.store');
        Route::get('pm/checklists/{checklist}/edit', [ChecklistController::class, 'edit'])->name('pm.checklists.edit');
        Route::put('pm/checklists/{checklist}', [ChecklistController::class, 'update'])->name('pm.checklists.update');
        Route::delete('pm/checklists/{checklist}', [ChecklistController::class, 'destroy'])->name('pm.checklists.destroy');
    });
    Route::middleware('permission:pm.pm.execute')->group(function () {
        Route::get('pm/schedules/{schedule}/complete', [PmScheduleController::class, 'completeForm'])->name('pm.schedules.complete');
        Route::post('pm/schedules/{schedule}/complete', [PmScheduleController::class, 'complete'])->name('pm.schedules.complete.store');
    });

    // ------------------------------------------------------------------ Safety (PTW & LOTO)
    Route::middleware('permission:pm.safety.view')->group(function () {
        Route::get('permits', [SafetyPermitController::class, 'index'])->name('permits.index');
        Route::get('permits/{permit}', [SafetyPermitController::class, 'show'])->name('permits.show');
        Route::get('permits/{permit}/print', [SafetyPermitController::class, 'print'])->name('permits.print');
    });
    Route::middleware('permission:pm.safety.manage')->group(function () {
        Route::get('permits/create', [SafetyPermitController::class, 'create'])->name('permits.create');
        Route::post('permits', [SafetyPermitController::class, 'store'])->name('permits.store');
        Route::get('permits/{permit}/edit', [SafetyPermitController::class, 'edit'])->name('permits.edit');
        Route::put('permits/{permit}', [SafetyPermitController::class, 'update'])->name('permits.update');
        Route::post('permits/{permit}/issue', [SafetyPermitController::class, 'issue'])->name('permits.issue');
        Route::post('permits/{permit}/loto', [SafetyPermitController::class, 'storeLoto'])->name('permits.loto.store');
        Route::post('permits/{permit}/loto/{loto}/deisolate', [SafetyPermitController::class, 'deisolate'])->name('permits.loto.deisolate');
    });
    Route::middleware('permission:pm.safety.approve')->group(function () {
        Route::post('permits/{permit}/approve', [SafetyPermitController::class, 'approve'])->name('permits.approve');
        Route::post('permits/{permit}/close', [SafetyPermitController::class, 'close'])->name('permits.close');
        Route::post('permits/{permit}/cancel', [SafetyPermitController::class, 'cancel'])->name('permits.cancel');
    });

    // ------------------------------------------------------------------ Spares & stock
    Route::middleware('permission:pm.stock.view')->group(function () {
        Route::get('spares', [SpareController::class, 'index'])->name('spares.index');
        Route::get('spares/{spare}', [SpareController::class, 'show'])->name('spares.show');
        Route::get('stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
        Route::get('stock/search', [StockController::class, 'search'])->name('stock.search');
    });
    Route::middleware('permission:pm.stock.manage')->group(function () {
        Route::get('spares/create', [SpareController::class, 'create'])->name('spares.create');
        Route::post('spares', [SpareController::class, 'store'])->name('spares.store');
        Route::get('spares/{spare}/edit', [SpareController::class, 'edit'])->name('spares.edit');
        Route::put('spares/{spare}', [SpareController::class, 'update'])->name('spares.update');
    });
    Route::post('stock/receive', [StockController::class, 'receive'])->name('stock.receive')->middleware('permission:pm.stock.receive');
    Route::middleware('permission:pm.stock.issue')->group(function () {
        Route::get('stock/issue', [StockController::class, 'issueForm'])->name('stock.issue');
        Route::post('stock/issue', [StockController::class, 'issue'])->name('stock.issue.store');
        Route::post('stock/return', [StockController::class, 'return'])->name('stock.return');
    });
    Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust')->middleware('permission:pm.stock.adjust');

    Route::middleware('permission:pm.purchase.view')->group(function () {
        Route::get('purchase-requests', [PurchaseRequestController::class, 'index'])->name('purchase-requests.index');
        Route::get('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('purchase-requests.show');
    });
    Route::middleware('permission:pm.purchase.manage')->group(function () {
        Route::get('purchase-requests/create', [PurchaseRequestController::class, 'create'])->name('purchase-requests.create');
        Route::post('purchase-requests', [PurchaseRequestController::class, 'store'])->name('purchase-requests.store');
        Route::post('purchase-requests/{purchaseRequest}/status', [PurchaseRequestController::class, 'updateStatus'])->name('purchase-requests.status');
    });

    // ------------------------------------------------------------------ Daily logs & handover
    Route::middleware('permission:pm.logs.view')->group(function () {
        Route::get('daily-logs', [DailyLogController::class, 'index'])->name('daily-logs.index');
        Route::get('daily-logs/{dailyLog}', [DailyLogController::class, 'show'])->name('daily-logs.show');
    });
    Route::middleware('permission:pm.logs.manage')->group(function () {
        Route::get('daily-logs/create', [DailyLogController::class, 'create'])->name('daily-logs.create');
        Route::post('daily-logs', [DailyLogController::class, 'store'])->name('daily-logs.store');
        Route::get('daily-logs/{dailyLog}/edit', [DailyLogController::class, 'edit'])->name('daily-logs.edit');
        Route::put('daily-logs/{dailyLog}', [DailyLogController::class, 'update'])->name('daily-logs.update');
    });
    Route::middleware('permission:pm.handover.view')->group(function () {
        Route::get('handovers', [ShiftHandoverController::class, 'index'])->name('handovers.index');
        Route::get('handovers/{handover}', [ShiftHandoverController::class, 'show'])->name('handovers.show');
    });
    Route::middleware('permission:pm.handover.manage')->group(function () {
        Route::get('handovers/create', [ShiftHandoverController::class, 'create'])->name('handovers.create');
        Route::post('handovers', [ShiftHandoverController::class, 'store'])->name('handovers.store');
        Route::post('handovers/{handover}/acknowledge', [ShiftHandoverController::class, 'acknowledge'])->name('handovers.acknowledge');
    });

    // ------------------------------------------------------------------ AMC & vendors
    Route::middleware('permission:pm.amc.view')->group(function () {
        Route::get('amc', [AmcController::class, 'index'])->name('amc.index');
        Route::get('amc/{amc}', [AmcController::class, 'show'])->name('amc.show');
        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
    });
    Route::middleware('permission:pm.amc.manage')->group(function () {
        Route::get('amc/create', [AmcController::class, 'create'])->name('amc.create');
        Route::post('amc', [AmcController::class, 'store'])->name('amc.store');
        Route::get('amc/{amc}/edit', [AmcController::class, 'edit'])->name('amc.edit');
        Route::put('amc/{amc}', [AmcController::class, 'update'])->name('amc.update');
        Route::post('amc/{amc}/visits', [AmcController::class, 'storeVisit'])->name('amc.visits.store');
        Route::post('amc/{amc}/visits/{visit}/complete', [AmcController::class, 'completeVisit'])->name('amc.visits.complete');
        Route::post('amc/{amc}/services', [AmcController::class, 'storeService'])->name('amc.services.store');
    });
    Route::middleware('permission:pm.vendors.manage')->group(function () {
        Route::get('vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    });

    // ------------------------------------------------------------------ Compliance & calibration
    Route::middleware('permission:pm.compliance.view')->group(function () {
        Route::get('compliances', [ComplianceController::class, 'index'])->name('compliances.index');
        Route::get('compliances/{compliance}', [ComplianceController::class, 'show'])->name('compliances.show');
        Route::get('calibrations', [CalibrationController::class, 'index'])->name('calibrations.index');
        Route::get('calibrations/{calibration}', [CalibrationController::class, 'show'])->name('calibrations.show');
    });
    Route::middleware('permission:pm.compliance.manage')->group(function () {
        Route::get('compliances/create', [ComplianceController::class, 'create'])->name('compliances.create');
        Route::post('compliances', [ComplianceController::class, 'store'])->name('compliances.store');
        Route::get('compliances/{compliance}/edit', [ComplianceController::class, 'edit'])->name('compliances.edit');
        Route::put('compliances/{compliance}', [ComplianceController::class, 'update'])->name('compliances.update');
        Route::post('compliances/{compliance}/records', [ComplianceController::class, 'storeRecord'])->name('compliances.records.store');
        Route::get('calibrations/create', [CalibrationController::class, 'create'])->name('calibrations.create');
        Route::post('calibrations', [CalibrationController::class, 'store'])->name('calibrations.store');
        Route::get('calibrations/{calibration}/edit', [CalibrationController::class, 'edit'])->name('calibrations.edit');
        Route::put('calibrations/{calibration}', [CalibrationController::class, 'update'])->name('calibrations.update');
        Route::post('calibrations/{calibration}/records', [CalibrationController::class, 'storeRecord'])->name('calibrations.records.store');
        Route::post('calibrations/{calibration}/status', [CalibrationController::class, 'updateStatus'])->name('calibrations.status');
    });

    // ------------------------------------------------------------------ Reports
    Route::middleware('permission:pm.reports.view')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('{report}', [ReportController::class, 'show'])->name('show')->where('report', '[a-z\-]+');
    });

    // ------------------------------------------------------------------ Administration
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:pm.users.manage')->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
            Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        });
        Route::resource('roles', RoleController::class)->except(['show'])->middleware('permission:pm.roles.manage');
        Route::middleware('permission:pm.settings.manage')->group(function () {
            Route::resource('plants', PlantController::class)->except(['show']);
            Route::resource('departments', DepartmentController::class)->except(['show']);
            Route::resource('categories', CategoryController::class)->except(['show']);
            Route::resource('shifts', ShiftController::class)->except(['show']);
            Route::resource('teams', TeamController::class)->except(['show']);
            Route::resource('log-types', LogTypeController::class)->except(['show']);
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        });
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index')->middleware('permission:pm.audit.view');
    });
});
