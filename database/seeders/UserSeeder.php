<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo user per role. Password for all: "password"
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $dept = fn (string $code) => Department::where('code', $code)->value('id');

        $users = [
            // employee_code, name, email, role slug, department, designation
            ['EMP-001', 'System Admin',      'admin@plant.com',       'super-admin',            'ADMIN', 'IT Administrator'],
            ['EMP-002', 'Ravi Operator',     'employee@plant.com',    'employee',               'PROD',  'Machine Operator'],
            ['EMP-003', 'Imran Inspector',   'inspector@plant.com',   'maintenance-inspector',  'MECH',  'Maintenance Inspector'],
            ['EMP-004', 'Tarun Expert',      'expert@plant.com',      'technical-expert',       'MECH',  'Technical Expert'],
            ['EMP-005', 'Sunil Supervisor',  'supervisor@plant.com',  'maintenance-supervisor', 'MECH',  'Maintenance Supervisor'],
            ['EMP-006', 'Tej Technician',    'technician@plant.com',  'technician',             'MECH',  'Technician'],
            ['EMP-007', 'Bhavna Approver',   'approver@plant.com',    'budget-approver',        'ADMIN', 'Plant Head'],
            ['EMP-008', 'Sameer Safety',     'safety@plant.com',      'safety-officer',         'QA',    'Safety Officer'],
            ['EMP-009', 'Sheetal Store',     'store@plant.com',       'store-manager',          'STORE', 'Store Manager'],
            ['EMP-010', 'Prakash Plant',     'operator@plant.com',    'plant-operator',         'UTIL',  'Plant Operator'],
            ['EMP-011', 'Chitra Compliance', 'compliance@plant.com',  'compliance-officer',     'QA',    'Compliance Officer'],
            ['EMP-012', 'Mahesh Manager',    'manager@plant.com',     'maintenance-manager',    'MECH',  'Maintenance Manager'],
            ['EMP-013', 'Meera Management',  'management@plant.com',  'management',             'ADMIN', 'Director'],
            ['EMP-014', 'Elan Electrician',  'electrician@plant.com', 'technician',             'ELEC',  'Electrician'],
        ];

        foreach ($users as [$code, $name, $email, $role, $deptCode, $designation]) {
            $user = User::firstOrCreate(['email' => $email], [
                'employee_code' => $code,
                'name'          => $name,
                'password'      => 'password',
                'department_id' => $dept($deptCode),
                'designation'   => $designation,
                'phone'         => '98765' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            ]);
            $user->roles()->syncWithoutDetaching([Role::where('slug', $role)->value('id')]);
        }

        // Team membership
        $mech = Team::where('name', 'Mechanical Team')->first();
        $elec = Team::where('name', 'Electrical Team')->first();
        $mech?->update(['lead_user_id' => User::where('email', 'supervisor@plant.com')->value('id')]);
        $mech?->members()->syncWithoutDetaching(User::whereIn('email', ['technician@plant.com', 'supervisor@plant.com'])->pluck('id'));
        $elec?->members()->syncWithoutDetaching(User::whereIn('email', ['electrician@plant.com'])->pluck('id'));
    }
}
