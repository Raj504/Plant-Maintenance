<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asset & Equipment registry.
 *
 * pm_assets           -> every machine / equipment / component. parent_id gives the hierarchy
 *                        (Factory > Department > Line > Machine > Motor).
 * pm_asset_readings   -> running-hour / meter readings recorded over time.
 * pm_capacity_logs    -> production output per run, used for the Machine Capacity report
 *                        (rated 200 kg / 8 h, ran 5 h and produced 100 kg => 80% efficiency).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained('pm_categories')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pm_assets')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->foreignId('plant_id')->nullable()->constrained('pm_plants')->nullOnDelete();
            $table->string('location', 150)->nullable();
            $table->string('machine_number', 50)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('installation_date')->nullable();
            $table->date('commissioning_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->string('criticality', 20)->default('medium')->index()->comment('critical | high | medium | low');
            $table->string('status', 30)->default('active')->index()->comment('active | under_maintenance | breakdown | idle | under_installation | decommissioned');
            $table->decimal('current_running_hours', 12, 2)->default(0);
            $table->decimal('expected_life_years', 5, 1)->nullable();
            // Rated capacity used by the capacity report e.g. 200 kg per 8 hours
            $table->decimal('rated_capacity', 12, 2)->nullable();
            $table->string('capacity_unit', 20)->nullable()->comment('kg, litre, pcs ...');
            $table->decimal('capacity_hours', 6, 2)->nullable()->comment('Hours in which rated capacity is achieved, e.g. 8');
            $table->text('specifications')->nullable();
            $table->text('remarks')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pm_asset_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('pm_assets')->cascadeOnDelete();
            $table->date('reading_date')->index();
            $table->string('reading_type', 30)->default('running_hours')->comment('running_hours | meter');
            $table->decimal('value', 12, 2)->comment('Cumulative running hours or meter value');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_capacity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('pm_assets')->cascadeOnDelete();
            $table->date('log_date')->index();
            $table->foreignId('shift_id')->nullable()->constrained('pm_shifts')->nullOnDelete();
            $table->decimal('hours_run', 6, 2);
            $table->decimal('output_quantity', 12, 2);
            $table->string('unit', 20)->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_capacity_logs');
        Schema::dropIfExists('pm_asset_readings');
        Schema::dropIfExists('pm_assets');
    }
};
