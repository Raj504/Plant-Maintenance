<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Bootstrap 5 pagination links
        Paginator::useBootstrapFive();

        /*
         * Every model route parameter must be numeric. This keeps "/requests/create"
         * from being captured by "/requests/{maintenanceRequest}" regardless of the
         * order routes are declared in routes/web.php.
         */
        foreach (['asset', 'maintenanceRequest', 'workOrder', 'task', 'plan', 'schedule', 'log', 'checklist', 'permit', 'loto',
                  'spare', 'purchaseRequest', 'dailyLog', 'handover', 'amc', 'visit', 'vendor', 'compliance', 'calibration',
                  'user', 'role', 'plant', 'department', 'category', 'shift', 'team', 'log_type', 'notification', 'attachment', 'bom'] as $param) {
            Route::pattern($param, '[0-9]+');
        }

        /*
         * RBAC integration with Laravel's Gate.
         * This makes @can('pm.assets.view') in Blade and $this->authorize('pm.assets.view')
         * in controllers work against our pm_permissions table.
         */
        Gate::before(function ($user, string $ability) {
            if (str_starts_with($ability, 'pm.')) {
                return $user->hasPermission($ability) ? true : false;
            }
            return null;
        });

        // @role('super-admin') ... @endrole
        Blade::if('role', fn (string $role) => auth()->check() && auth()->user()->hasRole($role));

        // Share unread notifications with the main layout header
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('headerNotifications', $user
                ? Notification::where('user_id', $user->id)->where('is_read', false)->latest()->limit(8)->get()
                : collect());
            $view->with('unreadCount', $user
                ? Notification::where('user_id', $user->id)->where('is_read', false)->count()
                : 0);
        });
    }
}
