<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Checklist;
use App\Models\Department;
use App\Models\LogType;
use App\Models\Plant;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Team;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Reference data a factory needs on day one. Safe to re-run.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------- Settings
        $settings = [
            ['pm.company_name', 'Demo Industries Pvt. Ltd.', 'Company name', 'general'],
            ['pm.currency_symbol', '₹', 'Currency symbol', 'general'],
            ['pm.due_soon_days', '30', 'Days before due date to show "due soon" alerts', 'alerts'],
            ['pm.pm_lookahead_days', '3', 'Days ahead to generate PM work orders', 'alerts'],
            ['pm.amc_expiry_alert_days', '30', 'Days before AMC expiry to alert', 'alerts'],
            ['pm.critical_response_hours', '2', 'Expected response for critical requests (hours)', 'alerts'],
            ['pm.labour_rate', '250', 'Default labour rate per hour', 'general'],
        ];
        foreach ($settings as [$key, $value, $label, $group]) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'label' => $label, 'group' => $group]);
        }

        // ---------------- Plant & departments
        $plant = Plant::firstOrCreate(['code' => 'PLANT-1'], ['name' => 'Main Factory', 'location' => 'Industrial Area, Phase 2']);

        $departments = [
            ['PROD', 'Production'],
            ['UTIL', 'Utilities'],
            ['ELEC', 'Electrical'],
            ['MECH', 'Mechanical Maintenance'],
            ['STORE', 'Stores'],
            ['QA', 'Quality & Compliance'],
            ['ADMIN', 'Administration'],
        ];
        foreach ($departments as [$code, $name]) {
            Department::firstOrCreate(['code' => $code], ['name' => $name, 'plant_id' => $plant->id]);
        }

        // ---------------- Shifts
        foreach ([['Shift A', '06:00', '14:00'], ['Shift B', '14:00', '22:00'], ['Shift C', '22:00', '06:00'], ['General', '09:00', '18:00']] as [$name, $start, $end]) {
            Shift::firstOrCreate(['name' => $name], ['start_time' => $start, 'end_time' => $end]);
        }

        // ---------------- Categories
        $categories = [
            'asset'       => ['Production Line', 'Mixing Machine', 'Packaging Machine', 'Grinding Machine', 'Motor', 'Gearbox', 'Pump', 'Boiler', 'Air Compressor', 'DG Set', 'Transformer', 'Electrical Panel', 'Conveyor', 'AHU / ECU', 'ETP Equipment', 'Instrument'],
            'maintenance' => ['Lubrication', 'Inspection', 'Cleaning', 'Overhaul', 'Calibration', 'Electrical Check', 'Mechanical Service'],
            'spare'       => ['Filters', 'Bearings', 'Belts', 'Lubricants', 'Electrical', 'Seals & Gaskets', 'Fasteners', 'Consumables'],
            'problem'     => ['Abnormal Noise', 'Vibration', 'Overheating', 'Leakage', 'Electrical Fault', 'Not Starting', 'Low Output', 'Safety Issue', 'Other'],
            'compliance'  => ['Boiler Inspection', 'Pressure Vessel Testing', 'Safety Inspection', 'Electrical Inspection', 'Lifting Equipment Inspection', 'Fire Safety Inspection', 'Pollution Control'],
        ];
        foreach ($categories as $type => $names) {
            foreach ($names as $name) {
                Category::firstOrCreate(['type' => $type, 'name' => $name]);
            }
        }

        // ---------------- Vendors
        $vendors = [
            ['VND-001', 'Atlas Compressor Services', 'Ramesh Kumar', '9876500001', 'service@atlascomp.example', 'Compressor AMC & repairs'],
            ['VND-002', 'PowerTech Electricals', 'Suresh Patel', '9876500002', 'support@powertech.example', 'Electrical panels, motors, transformers'],
            ['VND-003', 'Precision Calibration Lab', 'Anita Shah', '9876500003', 'lab@precisioncal.example', 'NABL calibration services'],
            ['VND-004', 'Bharat Bearings & Spares', 'Vikram Singh', '9876500004', 'sales@bharatbearings.example', 'Bearings, belts, filters supply'],
        ];
        foreach ($vendors as [$code, $name, $contact, $phone, $email, $services]) {
            Vendor::firstOrCreate(['code' => $code], compact('name') + ['contact_person' => $contact, 'phone' => $phone, 'email' => $email, 'services' => $services]);
        }

        // ---------------- Teams
        foreach ([['Mechanical Team', 'Mechanical repairs and overhauls'], ['Electrical Team', 'Electrical faults and panels'], ['Utility Team', 'Boiler, compressor, DG, ETP']] as [$name, $desc]) {
            Team::firstOrCreate(['name' => $name], ['description' => $desc]);
        }

        // ---------------- Log types (configurable daily log forms)
        $logTypes = [
            ['EQUIP', 'Equipment Running Log', 'General equipment hour-meter log', true, [
                ['key' => 'running_status', 'label' => 'Running Status', 'type' => 'select', 'options' => 'Running,Stopped,Standby', 'unit' => ''],
                ['key' => 'load_percent', 'label' => 'Load', 'type' => 'number', 'unit' => '%'],
            ]],
            ['FUEL', 'Coal / Fuel Register', 'Fuel stock movement per shift', false, [
                ['key' => 'fuel_type', 'label' => 'Fuel Type', 'type' => 'select', 'options' => 'Coal,Diesel,Petrol,Furnace Oil,LPG', 'unit' => ''],
                ['key' => 'opening_stock', 'label' => 'Opening Stock', 'type' => 'number', 'unit' => 'kg/L'],
                ['key' => 'received_qty', 'label' => 'Received Quantity', 'type' => 'number', 'unit' => 'kg/L'],
                ['key' => 'consumed_qty', 'label' => 'Consumed Quantity', 'type' => 'number', 'unit' => 'kg/L'],
                ['key' => 'closing_stock', 'label' => 'Closing Stock', 'type' => 'number', 'unit' => 'kg/L'],
            ]],
            ['ENERGY', 'Energy Register', 'Electricity import / export', true, [
                ['key' => 'energy_import', 'label' => 'Energy Import', 'type' => 'number', 'unit' => 'kWh'],
                ['key' => 'energy_export', 'label' => 'Energy Export', 'type' => 'number', 'unit' => 'kWh'],
                ['key' => 'max_demand', 'label' => 'Max Demand', 'type' => 'number', 'unit' => 'kVA'],
                ['key' => 'power_factor', 'label' => 'Power Factor', 'type' => 'number', 'unit' => ''],
            ]],
            ['AHU', 'ECU / AHU Shift Log', 'Air handling / environment control readings', false, [
                ['key' => 'temperature', 'label' => 'Temperature', 'type' => 'number', 'unit' => '°C'],
                ['key' => 'pressure', 'label' => 'Pressure', 'type' => 'number', 'unit' => 'Pa'],
                ['key' => 'humidity', 'label' => 'Humidity', 'type' => 'number', 'unit' => '%RH'],
                ['key' => 'running_status', 'label' => 'Running Status', 'type' => 'select', 'options' => 'Running,Stopped,Standby', 'unit' => ''],
            ]],
            ['ETP', 'Water / ETP Register', 'Effluent treatment plant readings', false, [
                ['key' => 'water_input', 'label' => 'Water Input', 'type' => 'number', 'unit' => 'KL'],
                ['key' => 'water_output', 'label' => 'Water Output', 'type' => 'number', 'unit' => 'KL'],
                ['key' => 'treated_qty', 'label' => 'Treated Quantity', 'type' => 'number', 'unit' => 'KL'],
                ['key' => 'ph', 'label' => 'pH', 'type' => 'number', 'unit' => ''],
                ['key' => 'tds', 'label' => 'TDS', 'type' => 'number', 'unit' => 'ppm'],
                ['key' => 'cod', 'label' => 'COD', 'type' => 'number', 'unit' => 'mg/L'],
            ]],
        ];
        foreach ($logTypes as [$code, $name, $desc, $meter, $fields]) {
            LogType::updateOrCreate(['code' => $code], ['name' => $name, 'description' => $desc, 'has_meter_readings' => $meter, 'fields' => $fields]);
        }

        // ---------------- PM checklists
        $checklists = [
            'Motor Monthly Check' => ['Check vibration', 'Check temperature', 'Check bearing condition', 'Check lubrication', 'Check alignment', 'Check electrical connections', 'Check noise', 'Clean motor', 'Record observations'],
            'Compressor Service'  => ['Check oil level', 'Replace oil filter', 'Replace air filter', 'Check belt tension', 'Check safety valve', 'Drain condensate', 'Check pressure switch', 'Record running hours'],
            'Boiler Daily Check'  => ['Check water level', 'Check pressure gauge', 'Check safety valve', 'Blow down', 'Check feed pump', 'Check burner', 'Inspect for leaks'],
            'General Machine PM'  => ['Visual inspection', 'Clean machine', 'Lubricate moving parts', 'Check guards and safety', 'Check fasteners', 'Trial run'],
        ];
        foreach ($checklists as $name => $items) {
            $checklist = Checklist::firstOrCreate(['name' => $name]);
            if ($checklist->items()->count() === 0) {
                foreach ($items as $i => $text) {
                    $checklist->items()->create(['item_text' => $text, 'sort_order' => $i + 1]);
                }
            }
        }
    }
}
