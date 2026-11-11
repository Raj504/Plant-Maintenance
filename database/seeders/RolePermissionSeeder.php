<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Creates every permission and the standard roles with their permission sets.
 * Safe to re-run: uses updateOrCreate / sync.
 *
 * Add a new permission here, run `php artisan db:seed --class=RolePermissionSeeder`
 * and it becomes available in Settings > Roles.
 */
class RolePermissionSeeder extends Seeder
{
    /** module => [permission => label] */
    public static function permissions(): array
    {
        return [
            'dashboard' => [
                'pm.dashboard.view' => 'View dashboard',
            ],
            'assets' => [
                'pm.assets.view'     => 'View assets',
                'pm.assets.create'   => 'Create assets',
                'pm.assets.edit'     => 'Edit assets',
                'pm.assets.delete'   => 'Delete assets',
                'pm.assets.readings' => 'Record running hours / capacity logs',
            ],
            'requests' => [
                'pm.requests.view'      => 'View maintenance requests',
                'pm.requests.create'    => 'Raise maintenance requests',
                'pm.requests.manage'    => 'Edit / cancel any request',
                'pm.requests.inspect'   => 'Record inspection',
                'pm.requests.assess'    => 'Record technical assessment',
                'pm.requests.approve'   => 'Approve / reject / send back (budget)',
                'pm.requests.emergency' => 'Authorise emergency maintenance',
            ],
            'workorders' => [
                'pm.workorders.view'    => 'View work orders',
                'pm.workorders.create'  => 'Create manual work orders',
                'pm.workorders.assign'  => 'Assign work orders',
                'pm.workorders.execute' => 'Execute work orders (technician)',
                'pm.workorders.verify'  => 'Verify and close work orders',
                'pm.workorders.cancel'  => 'Cancel work orders',
            ],
            'preventive' => [
                'pm.pm.view'    => 'View PM plans & schedules',
                'pm.pm.manage'  => 'Manage PM plans & checklists',
                'pm.pm.execute' => 'Complete PM checklists',
            ],
            'safety' => [
                'pm.safety.view'    => 'View permits & LOTO',
                'pm.safety.manage'  => 'Create permits & LOTO records',
                'pm.safety.approve' => 'Approve / close permits',
            ],
            'stock' => [
                'pm.stock.view'    => 'View spares & stock',
                'pm.stock.manage'  => 'Manage spare master & BOM',
                'pm.stock.receive' => 'Receive stock',
                'pm.stock.issue'   => 'Issue / return stock',
                'pm.stock.adjust'  => 'Stock adjustments',
                'pm.purchase.view'   => 'View purchase requisitions',
                'pm.purchase.manage' => 'Raise / process purchase requisitions',
            ],
            'logs' => [
                'pm.logs.view'       => 'View daily plant logs',
                'pm.logs.manage'     => 'Create / edit daily plant logs',
                'pm.handover.view'   => 'View shift handovers',
                'pm.handover.manage' => 'Create / acknowledge shift handovers',
            ],
            'amc' => [
                'pm.amc.view'       => 'View AMC contracts',
                'pm.amc.manage'     => 'Manage AMC contracts & vendor services',
                'pm.vendors.manage' => 'Manage vendors',
            ],
            'compliance' => [
                'pm.compliance.view'   => 'View compliance & calibration',
                'pm.compliance.manage' => 'Manage compliance & calibration',
            ],
            'reports' => [
                'pm.reports.view' => 'View & export reports',
            ],
            'admin' => [
                'pm.users.manage'    => 'Manage users',
                'pm.roles.manage'    => 'Manage roles & permissions',
                'pm.settings.manage' => 'Manage masters & settings',
                'pm.audit.view'      => 'View audit trail',
            ],
        ];
    }

    /** role slug => [name, description, permissions] ('*' = everything) */
    public static function roles(): array
    {
        $view = ['pm.dashboard.view', 'pm.assets.view', 'pm.requests.view', 'pm.workorders.view', 'pm.pm.view',
                 'pm.safety.view', 'pm.stock.view', 'pm.purchase.view', 'pm.logs.view', 'pm.handover.view',
                 'pm.amc.view', 'pm.compliance.view'];

        return [
            'super-admin' => ['Super Admin', 'Full access to everything', ['*']],
            'employee' => ['Employee / Requester', 'Raise and view own requests',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.requests.view', 'pm.requests.create']],
            'maintenance-inspector' => ['Maintenance Inspector', 'Inspect maintenance requests',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.requests.view', 'pm.requests.create', 'pm.requests.inspect', 'pm.workorders.view']],
            'technical-expert' => ['Technical Expert', 'Technical assessment of requests',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.requests.view', 'pm.requests.create', 'pm.requests.assess', 'pm.workorders.view', 'pm.stock.view']],
            'maintenance-supervisor' => ['Maintenance Supervisor', 'Assign and manage work orders',
                array_merge($view, ['pm.assets.create', 'pm.assets.edit', 'pm.assets.readings', 'pm.requests.create', 'pm.requests.manage',
                    'pm.workorders.create', 'pm.workorders.assign', 'pm.workorders.execute', 'pm.workorders.verify', 'pm.workorders.cancel',
                    'pm.pm.manage', 'pm.pm.execute', 'pm.safety.manage', 'pm.handover.manage', 'pm.purchase.manage', 'pm.reports.view'])],
            'technician' => ['Technician', 'Execute assigned maintenance',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.assets.readings', 'pm.requests.view', 'pm.requests.create', 'pm.workorders.view',
                 'pm.workorders.execute', 'pm.pm.view', 'pm.pm.execute', 'pm.safety.view', 'pm.safety.manage', 'pm.stock.view', 'pm.handover.view', 'pm.handover.manage']],
            'budget-approver' => ['Budget / Admin Approver', 'Approve or reject expenditure',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.requests.view', 'pm.requests.approve', 'pm.requests.emergency', 'pm.workorders.view', 'pm.purchase.view', 'pm.purchase.manage', 'pm.reports.view']],
            'safety-officer' => ['Safety Officer', 'PTW and LOTO control',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.workorders.view', 'pm.safety.view', 'pm.safety.manage', 'pm.safety.approve']],
            'store-manager' => ['Store Manager', 'Spare stock and material issue',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.workorders.view', 'pm.stock.view', 'pm.stock.manage', 'pm.stock.receive', 'pm.stock.issue',
                 'pm.stock.adjust', 'pm.purchase.view', 'pm.purchase.manage', 'pm.vendors.manage', 'pm.reports.view']],
            'plant-operator' => ['Plant Operator', 'Daily plant logs',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.assets.readings', 'pm.requests.view', 'pm.requests.create', 'pm.logs.view', 'pm.logs.manage', 'pm.handover.view', 'pm.handover.manage']],
            'compliance-officer' => ['Compliance Officer', 'Statutory and calibration records',
                ['pm.dashboard.view', 'pm.assets.view', 'pm.compliance.view', 'pm.compliance.manage', 'pm.amc.view', 'pm.reports.view']],
            'maintenance-manager' => ['Maintenance Manager', 'Overall maintenance supervision',
                ['*except-admin*']],
            'management' => ['Management', 'Dashboard and reports',
                array_merge($view, ['pm.reports.view'])],
            'system-administrator' => ['System Administrator', 'Configuration and access control',
                ['pm.dashboard.view', 'pm.users.manage', 'pm.roles.manage', 'pm.settings.manage', 'pm.audit.view', 'pm.vendors.manage']],
        ];
    }

    public function run(): void
    {
        $all = [];
        foreach (self::permissions() as $module => $perms) {
            foreach ($perms as $name => $label) {
                Permission::updateOrCreate(['name' => $name], ['module' => $module, 'label' => $label]);
                $all[] = $name;
            }
        }

        $adminPerms = array_keys(self::permissions()['admin']);

        foreach (self::roles() as $slug => [$name, $description, $perms]) {
            $role = Role::updateOrCreate(['slug' => $slug], [
                'name'        => $name,
                'description' => $description,
                'is_system'   => true,
            ]);

            if ($perms === ['*']) {
                $names = $all;
            } elseif ($perms === ['*except-admin*']) {
                $names = array_diff($all, $adminPerms);
            } else {
                $names = $perms;
            }

            $ids = Permission::whereIn('name', $names)->pluck('id');
            $role->permissions()->sync($ids);
        }
    }
}
