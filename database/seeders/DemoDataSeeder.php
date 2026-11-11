<?php

namespace Database\Seeders;

use App\Models\AmcContract;
use App\Models\Asset;
use App\Models\AssetBom;
use App\Models\AssetReading;
use App\Models\Calibration;
use App\Models\CapacityLog;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\Compliance;
use App\Models\Department;
use App\Models\Plant;
use App\Models\Shift;
use App\Models\Spare;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ApprovalWorkflowService;
use App\Services\DocumentNumberService;
use App\Services\MaintenanceRequestService;
use App\Services\PreventiveMaintenanceService;
use App\Services\StockLedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Realistic factory demo data: asset hierarchy, spares with opening stock, BOM,
 * PM plans, requests at different workflow stages, AMC, compliance, calibration.
 * Uses the real services so the data is exactly what the app would produce.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Asset::count() > 0) {
            return; // already seeded
        }

        $plant   = Plant::first();
        $dept    = fn (string $code) => Department::where('code', $code)->value('id');
        $cat     = fn (string $name) => Category::where('type', 'asset')->where('name', $name)->value('id');
        $admin   = User::where('email', 'admin@plant.com')->first();
        Auth::login($admin);

        // ------------------------------------------------------------ Assets (hierarchy)
        $mk = function (array $a) use ($plant, $admin) {
            return Asset::create(array_merge([
                'plant_id' => $plant->id, 'status' => 'active', 'criticality' => 'medium', 'created_by' => $admin->id,
            ], $a));
        };

        $line1 = $mk(['asset_code' => 'LINE-01', 'name' => 'Production Line 1', 'category_id' => $cat('Production Line'), 'department_id' => $dept('PROD'), 'location' => 'Shop Floor A', 'criticality' => 'critical']);
        $mixer = $mk(['asset_code' => 'MIX-01', 'name' => 'Mixing Machine 1', 'parent_id' => $line1->id, 'category_id' => $cat('Mixing Machine'), 'department_id' => $dept('PROD'), 'location' => 'Shop Floor A', 'machine_number' => 'M-101', 'manufacturer' => 'Hindustan Mixers', 'model_number' => 'HM-500', 'serial_number' => 'HM500-2021-0456', 'installation_date' => '2021-03-10', 'commissioning_date' => '2021-03-20', 'purchase_date' => '2021-02-01', 'warranty_expiry_date' => '2024-03-20', 'criticality' => 'critical', 'current_running_hours' => 6420, 'expected_life_years' => 15, 'rated_capacity' => 500, 'capacity_unit' => 'kg', 'capacity_hours' => 8, 'specifications' => 'Capacity 500 kg/batch, 15 kW motor, 3 phase']);
        $motor = $mk(['asset_code' => 'MIX-01-MTR', 'name' => 'Mixer Main Motor', 'parent_id' => $mixer->id, 'category_id' => $cat('Motor'), 'department_id' => $dept('PROD'), 'manufacturer' => 'Crompton', 'model_number' => '15kW-4P', 'serial_number' => 'CG-15-7781', 'criticality' => 'high', 'current_running_hours' => 6420]);
        $gear  = $mk(['asset_code' => 'MIX-01-GBX', 'name' => 'Mixer Gearbox', 'parent_id' => $mixer->id, 'category_id' => $cat('Gearbox'), 'department_id' => $dept('PROD'), 'manufacturer' => 'Elecon', 'criticality' => 'high', 'current_running_hours' => 6420]);
        $grind = $mk(['asset_code' => 'GRD-01', 'name' => 'Grinding Machine 1', 'parent_id' => $line1->id, 'category_id' => $cat('Grinding Machine'), 'department_id' => $dept('PROD'), 'location' => 'Shop Floor A', 'machine_number' => 'M-102', 'manufacturer' => 'Pulverizer India', 'model_number' => 'PG-200', 'installation_date' => '2020-06-15', 'criticality' => 'high', 'current_running_hours' => 9850, 'rated_capacity' => 200, 'capacity_unit' => 'kg', 'capacity_hours' => 8, 'specifications' => 'Rated 200 kg grinding per 8 hour shift']);
        $pack  = $mk(['asset_code' => 'PKG-01', 'name' => 'Packaging Machine 1', 'parent_id' => $line1->id, 'category_id' => $cat('Packaging Machine'), 'department_id' => $dept('PROD'), 'location' => 'Shop Floor A', 'machine_number' => 'M-103', 'manufacturer' => 'PackWell', 'criticality' => 'medium', 'current_running_hours' => 4100, 'rated_capacity' => 4000, 'capacity_unit' => 'pcs', 'capacity_hours' => 8]);
        $conv  = $mk(['asset_code' => 'CNV-01', 'name' => 'Belt Conveyor 1', 'parent_id' => $line1->id, 'category_id' => $cat('Conveyor'), 'department_id' => $dept('PROD'), 'criticality' => 'medium', 'current_running_hours' => 5200]);

        $boiler = $mk(['asset_code' => 'BLR-01', 'name' => 'Steam Boiler 2 TPH', 'category_id' => $cat('Boiler'), 'department_id' => $dept('UTIL'), 'location' => 'Boiler House', 'manufacturer' => 'Thermax', 'model_number' => 'CPFB-2', 'serial_number' => 'TX-2019-889', 'installation_date' => '2019-01-10', 'criticality' => 'critical', 'current_running_hours' => 21500]);
        $comp   = $mk(['asset_code' => 'CMP-01', 'name' => 'Air Compressor 75 HP', 'category_id' => $cat('Air Compressor'), 'department_id' => $dept('UTIL'), 'location' => 'Compressor Room', 'manufacturer' => 'Atlas Copco', 'model_number' => 'GA-55', 'serial_number' => 'AC-GA55-33021', 'installation_date' => '2020-09-01', 'warranty_expiry_date' => '2023-09-01', 'criticality' => 'critical', 'current_running_hours' => 12480]);
        $dg     = $mk(['asset_code' => 'DG-01', 'name' => 'DG Set 500 kVA', 'category_id' => $cat('DG Set'), 'department_id' => $dept('UTIL'), 'location' => 'DG Yard', 'manufacturer' => 'Cummins', 'model_number' => 'C500D5', 'criticality' => 'high', 'current_running_hours' => 1860]);
        $ahu    = $mk(['asset_code' => 'AHU-01', 'name' => 'AHU Packing Hall', 'category_id' => $cat('AHU / ECU'), 'department_id' => $dept('UTIL'), 'location' => 'Packing Hall Roof', 'criticality' => 'medium', 'current_running_hours' => 15000]);
        $etp    = $mk(['asset_code' => 'ETP-01', 'name' => 'Effluent Treatment Plant', 'category_id' => $cat('ETP Equipment'), 'department_id' => $dept('UTIL'), 'location' => 'ETP Area', 'criticality' => 'high']);

        $trafo  = $mk(['asset_code' => 'TRF-01', 'name' => 'Transformer 1000 kVA', 'category_id' => $cat('Transformer'), 'department_id' => $dept('ELEC'), 'location' => 'Substation', 'manufacturer' => 'Kirloskar', 'criticality' => 'critical', 'installation_date' => '2018-05-01']);
        $panel  = $mk(['asset_code' => 'PNL-01', 'name' => 'Main LT Panel', 'category_id' => $cat('Electrical Panel'), 'department_id' => $dept('ELEC'), 'location' => 'Substation', 'criticality' => 'high']);

        // Readings history for the compressor
        foreach ([[-90, 12000], [-60, 12160], [-30, 12320], [0, 12480]] as [$days, $hrs]) {
            AssetReading::create(['asset_id' => $comp->id, 'reading_date' => now()->addDays($days)->toDateString(), 'reading_type' => 'running_hours', 'value' => $hrs, 'recorded_by' => $admin->id]);
        }

        // Capacity logs for grinder (the 200 kg / 8 h example) and mixer
        $shiftA = Shift::where('name', 'Shift A')->value('id');
        $shiftB = Shift::where('name', 'Shift B')->value('id');
        $operator = User::where('email', 'employee@plant.com')->value('id');
        $capacity = [
            [$grind, -1, $shiftA, 5, 100], [$grind, -1, $shiftB, 8, 190], [$grind, -2, $shiftA, 8, 205], [$grind, -3, $shiftA, 6, 120], [$grind, -4, $shiftA, 7.5, 170],
            [$mixer, -1, $shiftA, 8, 480], [$mixer, -2, $shiftA, 8, 450], [$mixer, -3, $shiftA, 6, 390],
            [$pack, -1, $shiftA, 8, 3600], [$pack, -2, $shiftA, 7, 3300],
        ];
        foreach ($capacity as [$asset, $days, $shift, $hours, $qty]) {
            CapacityLog::create(['asset_id' => $asset->id, 'log_date' => now()->addDays($days)->toDateString(), 'shift_id' => $shift, 'hours_run' => $hours, 'output_quantity' => $qty, 'unit' => $asset->capacity_unit, 'operator_id' => $operator, 'created_by' => $admin->id]);
        }

        // ------------------------------------------------------------ Spares + opening stock
        $spareCat = fn (string $name) => Category::where('type', 'spare')->where('name', $name)->value('id');
        $vendor   = fn (string $code) => Vendor::where('code', $code)->value('id');
        $ledger   = app(StockLedgerService::class);

        $spares = [
            ['SP-001', 'Oil Filter GA-55', 'Filters', 'Nos', 2, 4, 1850, 'VND-001', 'Rack A1', 6],
            ['SP-002', 'Air Filter GA-55', 'Filters', 'Nos', 2, 4, 2400, 'VND-001', 'Rack A1', 3],
            ['SP-003', 'Oil Separator GA-55', 'Filters', 'Nos', 1, 2, 9500, 'VND-001', 'Rack A2', 1],
            ['SP-004', 'Bearing 6310 ZZ', 'Bearings', 'Nos', 4, 8, 650, 'VND-004', 'Rack B1', 12],
            ['SP-005', 'Bearing 6206 2RS', 'Bearings', 'Nos', 4, 8, 320, 'VND-004', 'Rack B1', 2],
            ['SP-006', 'V-Belt B-68', 'Belts', 'Nos', 4, 6, 420, 'VND-004', 'Rack B2', 10],
            ['SP-007', 'Compressor Oil Roto-Inject (20L)', 'Lubricants', 'Can', 1, 2, 7800, 'VND-001', 'Oil Store', 3],
            ['SP-008', 'Gear Oil EP-320 (20L)', 'Lubricants', 'Can', 1, 2, 5200, 'VND-004', 'Oil Store', 4],
            ['SP-009', 'Contactor 3TF 40A', 'Electrical', 'Nos', 2, 3, 1450, 'VND-002', 'Rack E1', 5],
            ['SP-010', 'MCB 32A 3P', 'Electrical', 'Nos', 3, 5, 900, 'VND-002', 'Rack E1', 8],
            ['SP-011', 'Mechanical Seal 40mm', 'Seals & Gaskets', 'Nos', 2, 3, 2100, 'VND-004', 'Rack C1', 0],
            ['SP-012', 'Boiler Gauge Glass', 'Seals & Gaskets', 'Nos', 2, 2, 650, 'VND-004', 'Rack C2', 4],
        ];
        $spareModels = [];
        foreach ($spares as [$code, $name, $category, $uom, $min, $reorder, $cost, $vnd, $loc, $opening]) {
            $spare = Spare::create(['code' => $code, 'name' => $name, 'category_id' => $spareCat($category), 'uom' => $uom, 'min_stock' => $min, 'reorder_level' => $reorder, 'unit_cost' => $cost, 'preferred_vendor_id' => $vendor($vnd), 'store_location' => $loc]);
            $spareModels[$code] = $spare;
            if ($opening > 0) {
                $ledger->record(['spare_id' => $spare->id, 'type' => 'opening', 'quantity' => $opening, 'unit_cost' => $cost, 'transaction_date' => now()->subDays(120)->toDateString(), 'reference_no' => 'OPENING', 'remarks' => 'Opening stock']);
            }
        }

        // BOM
        $bom = [
            [$comp, 'SP-001', 1, 'critical', 2000], [$comp, 'SP-002', 1, 'critical', 2000], [$comp, 'SP-003', 1, 'high', 4000], [$comp, 'SP-007', 1, 'high', 2000], [$comp, 'SP-006', 2, 'medium', 4000],
            [$motor, 'SP-004', 2, 'critical', 8000], [$gear, 'SP-008', 1, 'high', 4000], [$gear, 'SP-004', 2, 'high', 8000],
            [$panel, 'SP-009', 2, 'high', null], [$panel, 'SP-010', 4, 'medium', null],
            [$boiler, 'SP-012', 1, 'critical', 2000], [$etp, 'SP-011', 1, 'high', 6000], [$conv, 'SP-005', 4, 'medium', 6000], [$conv, 'SP-006', 2, 'medium', 4000],
        ];
        foreach ($bom as [$asset, $code, $qty, $crit, $interval]) {
            AssetBom::create(['asset_id' => $asset->id, 'spare_id' => $spareModels[$code]->id, 'quantity' => $qty, 'unit' => $spareModels[$code]->uom, 'criticality' => $crit, 'preferred_vendor_id' => $spareModels[$code]->preferred_vendor_id, 'replacement_interval_days' => $interval ? intdiv($interval, 24) : null]);
        }

        // ------------------------------------------------------------ PM plans
        $pm = app(PreventiveMaintenanceService::class);
        $mechTeam = Team::where('name', 'Mechanical Team')->value('id');
        $utilTeam = Team::where('name', 'Utility Team')->value('id');
        $tech = User::where('email', 'technician@plant.com')->value('id');
        $chk = fn (string $name) => Checklist::where('name', $name)->value('id');
        $mcat = fn (string $name) => Category::where('type', 'maintenance')->where('name', $name)->value('id');

        $pm->createPlan(['asset_id' => $comp->id, 'name' => 'Compressor 500 hr / 30 day service', 'category_id' => $mcat('Mechanical Service'), 'trigger_type' => 'both', 'frequency' => 'monthly', 'interval_days' => 30, 'interval_hours' => 500, 'last_done_date' => now()->subDays(25)->toDateString(), 'last_done_hours' => 12100, 'team_id' => $utilTeam, 'assigned_to' => $tech, 'checklist_id' => $chk('Compressor Service'), 'estimated_duration_hours' => 4, 'estimated_cost' => 6000, 'is_active' => true]);
        $pm->createPlan(['asset_id' => $mixer->id, 'component_id' => $motor->id, 'name' => 'Mixer motor monthly check', 'category_id' => $mcat('Inspection'), 'trigger_type' => 'calendar', 'frequency' => 'monthly', 'last_done_date' => now()->subDays(35)->toDateString(), 'team_id' => $mechTeam, 'assigned_to' => $tech, 'checklist_id' => $chk('Motor Monthly Check'), 'estimated_duration_hours' => 2, 'estimated_cost' => 500, 'is_active' => true]);
        $pm->createPlan(['asset_id' => $boiler->id, 'name' => 'Boiler daily check', 'category_id' => $mcat('Inspection'), 'trigger_type' => 'calendar', 'frequency' => 'daily', 'last_done_date' => now()->subDay()->toDateString(), 'team_id' => $utilTeam, 'checklist_id' => $chk('Boiler Daily Check'), 'estimated_duration_hours' => 0.5, 'is_active' => true]);
        $pm->createPlan(['asset_id' => $gear->id, 'name' => 'Gearbox oil change 2000 hrs', 'category_id' => $mcat('Lubrication'), 'trigger_type' => 'running_hours', 'interval_hours' => 2000, 'last_done_hours' => 5000, 'team_id' => $mechTeam, 'assigned_to' => $tech, 'checklist_id' => $chk('General Machine PM'), 'estimated_duration_hours' => 3, 'estimated_cost' => 5500, 'is_active' => true]);
        $pm->createPlan(['asset_id' => $pack->id, 'name' => 'Packaging machine quarterly PM', 'category_id' => $mcat('Mechanical Service'), 'trigger_type' => 'calendar', 'frequency' => 'quarterly', 'last_done_date' => now()->subDays(40)->toDateString(), 'team_id' => $mechTeam, 'checklist_id' => $chk('General Machine PM'), 'estimated_duration_hours' => 6, 'estimated_cost' => 3000, 'is_active' => true]);
        $pm->createPlan(['asset_id' => $dg->id, 'name' => 'DG set 250 hr service', 'category_id' => $mcat('Mechanical Service'), 'trigger_type' => 'running_hours', 'interval_hours' => 250, 'last_done_hours' => 1700, 'team_id' => $utilTeam, 'checklist_id' => $chk('General Machine PM'), 'estimated_duration_hours' => 3, 'estimated_cost' => 8000, 'is_active' => true]);

        // ------------------------------------------------------------ Requests at various stages
        $requests  = app(MaintenanceRequestService::class);
        $workflow  = app(ApprovalWorkflowService::class);
        $problem   = fn (string $name) => Category::where('type', 'problem')->where('name', $name)->value('id');

        $employee  = User::where('email', 'employee@plant.com')->first();
        $inspector = User::where('email', 'inspector@plant.com')->first();
        $expert    = User::where('email', 'expert@plant.com')->first();
        $approver  = User::where('email', 'approver@plant.com')->first();

        // R1: just submitted (critical)
        Auth::login($employee);
        $r1 = $requests->create(['asset_id' => $mixer->id, 'component_id' => $motor->id, 'request_type' => 'breakdown', 'priority' => 'critical', 'shift_id' => $shiftA, 'problem_description' => 'Mixer main motor tripping on overload after 10 minutes of running. Loud humming noise.', 'problem_category_id' => $problem('Electrical Fault'), 'machine_stopped' => true, 'production_affected' => true]);

        // R2: inspected, waiting for assessment
        $r2 = $requests->create(['asset_id' => $comp->id, 'request_type' => 'corrective', 'priority' => 'high', 'shift_id' => $shiftB, 'problem_description' => 'Compressor discharge temperature high (105 C). Oil level dropping.', 'problem_category_id' => $problem('Overheating'), 'machine_stopped' => false, 'production_affected' => false]);
        Auth::login($inspector);
        $workflow->recordInspection($r2, ['machine_condition' => 'Running with high temperature', 'actual_problem' => 'Oil cooler fins choked with dust, oil separator saturated.', 'root_cause' => 'Dusty environment, service overdue', 'machine_running' => true, 'safety_risk' => 'medium', 'production_impact' => 'partial', 'temporary_solution' => 'Cleaned cooler fins externally', 'required_action' => 'Replace oil separator and oil, full service', 'remarks' => 'Attend within 2 days', 'recommendation' => 'proceed']);

        // R3: assessed, waiting for budget approval
        Auth::login($employee);
        $r3 = $requests->create(['asset_id' => $conv->id, 'request_type' => 'mechanical', 'priority' => 'medium', 'shift_id' => $shiftA, 'problem_description' => 'Conveyor belt slipping and tracking to one side.', 'problem_category_id' => $problem('Low Output'), 'machine_stopped' => false, 'production_affected' => true]);
        Auth::login($inspector);
        $workflow->recordInspection($r3, ['machine_condition' => 'Running', 'actual_problem' => 'Tail pulley bearing worn, belt tension low', 'root_cause' => 'Bearing life exceeded', 'machine_running' => true, 'safety_risk' => 'low', 'production_impact' => 'partial', 'required_action' => 'Replace tail pulley bearings and adjust tension', 'recommendation' => 'proceed']);
        Auth::login($expert);
        $workflow->recordAssessment($r3, ['failure_nature' => 'Bearing wear on tail pulley', 'probable_root_cause' => 'Bearings exceeded service life, no lubrication schedule', 'action_required' => 'replace', 'required_manpower' => '2 fitters, 3 hours', 'external_vendor_required' => false, 'estimated_downtime_hours' => 3, 'estimated_labour_cost' => 1500, 'estimated_material_cost' => 1280, 'estimated_service_cost' => 0, 'permit_required' => false, 'recommendation' => 'Replace 4 nos bearing 6206, re-tension belt, add to PM plan'],
            [['spare_id' => $spareModels['SP-005']->id, 'quantity' => 4, 'estimated_cost' => 1280]]);

        // R4: approved -> work order created (in progress)
        Auth::login($employee);
        $r4 = $requests->create(['asset_id' => $panel->id, 'request_type' => 'electrical', 'priority' => 'high', 'shift_id' => $shiftA, 'problem_description' => 'Burning smell from main LT panel, contactor for mixer feeder overheating.', 'problem_category_id' => $problem('Overheating'), 'machine_stopped' => false, 'production_affected' => false]);
        Auth::login($inspector);
        $workflow->recordInspection($r4, ['machine_condition' => 'Energised', 'actual_problem' => 'Contactor contacts pitted, loose termination', 'root_cause' => 'Loose connection causing arcing', 'machine_running' => true, 'safety_risk' => 'high', 'production_impact' => 'none', 'required_action' => 'Replace contactor, tighten all terminations under permit', 'recommendation' => 'proceed']);
        Auth::login($expert);
        $workflow->recordAssessment($r4, ['failure_nature' => 'Electrical contactor failure', 'probable_root_cause' => 'Loose termination', 'action_required' => 'replace', 'required_manpower' => '1 electrician, 2 hours', 'external_vendor_required' => false, 'estimated_downtime_hours' => 2, 'estimated_labour_cost' => 800, 'estimated_material_cost' => 2900, 'estimated_service_cost' => 0, 'permit_required' => true, 'recommendation' => 'Replace 2 contactors under electrical PTW with LOTO'],
            [['spare_id' => $spareModels['SP-009']->id, 'quantity' => 2, 'estimated_cost' => 2900]]);
        Auth::login($approver);
        $workflow->decide($r4, 'approved', 'Approved. Safety first, use permit.', 3700);

        // R5: closed one (history) via full flow
        Auth::login($employee);
        $r5 = $requests->create(['asset_id' => $pack->id, 'request_type' => 'breakdown', 'priority' => 'medium', 'shift_id' => $shiftB, 'request_date' => now()->subDays(12), 'problem_description' => 'Packaging machine sealing jaw not heating.', 'problem_category_id' => $problem('Not Starting'), 'machine_stopped' => true, 'production_affected' => true]);
        Auth::login($inspector);
        $workflow->recordInspection($r5, ['machine_condition' => 'Stopped', 'actual_problem' => 'Heater element open circuit', 'root_cause' => 'Element burnt', 'machine_running' => false, 'safety_risk' => 'low', 'production_impact' => 'full', 'required_action' => 'Replace heater element', 'recommendation' => 'proceed']);
        Auth::login($expert);
        $workflow->recordAssessment($r5, ['failure_nature' => 'Heater element failure', 'probable_root_cause' => 'End of life', 'action_required' => 'replace', 'required_manpower' => '1 electrician', 'external_vendor_required' => false, 'estimated_downtime_hours' => 2, 'estimated_labour_cost' => 500, 'estimated_material_cost' => 1200, 'estimated_service_cost' => 0, 'recommendation' => 'Replace element']);
        Auth::login($approver);
        $workflow->decide($r5, 'approved', 'OK');
        $wo5 = $r5->fresh()->workOrder;
        $sup = User::where('email', 'supervisor@plant.com')->first();
        Auth::login($sup);
        $wos = app(\App\Services\WorkOrderService::class);
        $wos->assign($wo5, ['team_id' => $mechTeam, 'assigned_to' => $tech, 'planned_start' => now()->subDays(11)->toDateString(), 'planned_end' => now()->subDays(10)->toDateString()]);
        Auth::login(User::find($tech));
        $wos->start($wo5);
        $wo5->labour()->create(['user_id' => $tech, 'work_date' => now()->subDays(10)->toDateString(), 'hours' => 2.5, 'rate' => 250]);
        $wos->complete($wo5, ['actual_problem_found' => 'Heater element open, thermocouple OK', 'work_performed' => 'Replaced heater element, tested sealing at 160 C', 'repair_remarks' => 'Machine OK', 'downtime_hours' => 5, 'other_cost' => 1200]);
        Auth::login($sup);
        $wos->verify($wo5, ['machine_operation_ok' => true, 'safety_ok' => true, 'repair_quality_ok' => true, 'production_ready' => true, 'test_results' => 'Seal strength OK on 20 samples', 'decision' => 'approved', 'remarks' => 'Verified, machine released to production', 'final_machine_status' => 'active']);

        // R6: rejected at inspection
        Auth::login($employee);
        $r6 = $requests->create(['asset_id' => $ahu->id, 'request_type' => 'improvement', 'priority' => 'low', 'problem_description' => 'Please install an extra fan near the packing table.', 'problem_category_id' => $problem('Other')]);
        Auth::login($inspector);
        $workflow->recordInspection($r6, ['machine_condition' => 'OK', 'actual_problem' => 'Not a maintenance issue', 'machine_running' => true, 'safety_risk' => 'none', 'production_impact' => 'none', 'recommendation' => 'reject', 'rejection_reason' => 'This is a facility improvement, raise through admin capex form.']);

        // ------------------------------------------------------------ AMC
        Auth::login($admin);
        $amc = AmcContract::create(['amc_no' => DocumentNumberService::next('amc'), 'vendor_id' => $vendor('VND-001'), 'name' => 'Compressor comprehensive AMC', 'start_date' => now()->subMonths(11)->toDateString(), 'end_date' => now()->addDays(20)->toDateString(), 'contract_value' => 85000, 'payment_terms' => 'Quarterly in advance', 'service_frequency' => 'quarterly', 'scope_of_work' => 'Quarterly service, filters and oil included, breakdown attendance within 24 hrs', 'response_time_hours' => 24, 'resolution_time_hours' => 72, 'contact_person' => 'Ramesh Kumar', 'contact_number' => '9876500001', 'status' => 'active', 'created_by' => $admin->id]);
        $amc->assets()->sync([$comp->id]);
        foreach ([-8, -5, -2, 1] as $m) {
            $date = now()->addMonths($m)->startOfMonth()->addDays(9);
            $amc->visits()->create(['scheduled_date' => $date->toDateString(), 'actual_date' => $m < 0 ? $date->toDateString() : null, 'status' => $m < 0 ? 'completed' : 'scheduled', 'work_done' => $m < 0 ? 'Quarterly service done' : null]);
        }
        $amc2 = AmcContract::create(['amc_no' => DocumentNumberService::next('amc'), 'vendor_id' => $vendor('VND-002'), 'name' => 'Transformer & panel annual maintenance', 'start_date' => now()->subMonths(4)->toDateString(), 'end_date' => now()->addMonths(8)->toDateString(), 'contract_value' => 45000, 'payment_terms' => 'Yearly', 'service_frequency' => 'half_yearly', 'scope_of_work' => 'Oil testing, thermography, panel cleaning', 'response_time_hours' => 48, 'resolution_time_hours' => 96, 'status' => 'active', 'created_by' => $admin->id]);
        $amc2->assets()->sync([$trafo->id, $panel->id]);

        $amc->services()->create(['vendor_id' => $vendor('VND-001'), 'service_date' => now()->subMonths(2)->toDateString(), 'response_hours' => 20, 'resolution_hours' => 30, 'response_within_sla' => true, 'resolution_within_sla' => true, 'work_quality' => 4, 'attended' => true, 'repeat_failure' => false, 'cost' => 0, 'feedback' => 'Good service', 'recorded_by' => $admin->id]);
        $amc->services()->create(['vendor_id' => $vendor('VND-001'), 'service_date' => now()->subMonths(5)->toDateString(), 'response_hours' => 36, 'resolution_hours' => 60, 'response_within_sla' => false, 'resolution_within_sla' => true, 'work_quality' => 3, 'attended' => true, 'repeat_failure' => false, 'cost' => 2500, 'feedback' => 'Late response', 'recorded_by' => $admin->id]);

        // ------------------------------------------------------------ Compliance & calibration
        $compliances = [
            [$boiler, 'Boiler Inspection', 'Annual boiler inspection', 'Directorate of Boilers', 'Boiler Inspector', -340, 365, 'BLR/2025/1182', 'passed'],
            [$comp, 'Pressure Vessel Testing', 'Air receiver hydraulic test', 'Factory Inspectorate', 'Competent Person - ABC Inspections', -700, 730, 'PV-2024-233', 'passed'],
            [$trafo, 'Electrical Inspection', 'Annual electrical installation inspection', 'Chief Electrical Inspector', 'Govt Electrical Inspector', -380, 365, 'EI/2024/5521', 'passed'],
            [$line1, 'Fire Safety Inspection', 'Fire NOC renewal', 'Fire Department', 'Fire Department', -200, 365, 'FIRE/NOC/2025/88', 'passed'],
            [$etp, 'Pollution Control', 'Consent to operate renewal', 'State Pollution Control Board', 'SPCB', -300, 365, 'CTO/2025/1190', 'passed'],
        ];
        foreach ($compliances as [$asset, $type, $name, $authority, $agency, $daysAgo, $freq, $cert, $result]) {
            $last = now()->addDays($daysAgo);
            $c = Compliance::create(['compliance_no' => DocumentNumberService::next('compliance'), 'asset_id' => $asset->id, 'compliance_type' => $type, 'inspection_name' => $name, 'authority' => $authority, 'agency' => $agency, 'department_id' => $asset->department_id, 'frequency_days' => $freq, 'last_inspection_date' => $last->toDateString(), 'next_due_date' => $last->copy()->addDays($freq)->toDateString(), 'certificate_no' => $cert, 'result' => $result, 'created_by' => $admin->id]);
            $c->records()->create(['inspection_date' => $last->toDateString(), 'next_due_date' => $c->next_due_date, 'certificate_no' => $cert, 'agency' => $agency, 'result' => $result, 'recorded_by' => $admin->id]);
            $c->refreshStatus(30);
            $c->save();
        }

        $calibrations = [
            ['INS-001', 'Boiler Pressure Gauge 0-16 bar', $boiler, 'PG-7781', -350, 365, 'CAL/2025/0412', 'passed'],
            ['INS-002', 'Weighing Scale 500 kg', $mixer, 'WS-2210', -380, 365, 'CAL/2024/0991', 'passed'],
            ['INS-003', 'Digital Thermometer', $comp, 'DT-0098', -170, 180, 'CAL/2025/0620', 'passed'],
            ['INS-004', 'pH Meter ETP', $etp, 'PH-3310', -100, 180, 'CAL/2025/0755', 'passed'],
            ['INS-005', 'Clamp Meter 1000A', $panel, 'CM-5561', -20, 365, 'CAL/2025/0901', 'passed'],
        ];
        foreach ($calibrations as [$code, $name, $asset, $serial, $daysAgo, $freq, $cert, $result]) {
            $last = now()->addDays($daysAgo);
            $c = Calibration::create(['instrument_code' => $code, 'instrument_name' => $name, 'asset_id' => $asset->id, 'department_id' => $asset->department_id, 'serial_number' => $serial, 'agency' => 'Precision Calibration Lab', 'frequency_days' => $freq, 'last_calibration_date' => $last->toDateString(), 'next_due_date' => $last->copy()->addDays($freq)->toDateString(), 'certificate_no' => $cert, 'result' => $result, 'created_by' => $admin->id]);
            $c->records()->create(['calibration_date' => $last->toDateString(), 'next_due_date' => $c->next_due_date, 'certificate_no' => $cert, 'agency' => 'Precision Calibration Lab', 'result' => $result, 'recorded_by' => $admin->id]);
            $c->refreshStatus(30);
            $c->save();
        }

        Auth::logout();
    }
}
