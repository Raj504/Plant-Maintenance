<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statutory compliance and calibration.
 *
 * pm_compliances         -> one row per statutory item (Boiler inspection for Boiler-1). Holds the CURRENT dates.
 * pm_compliance_records  -> every inspection done over time (history + old certificates are kept).
 * pm_calibrations        -> one row per instrument with current calibration dates.
 * pm_calibration_records -> every calibration done over time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_compliances', function (Blueprint $table) {
            $table->id();
            $table->string('compliance_no', 30)->unique();
            $table->foreignId('asset_id')->nullable()->constrained('pm_assets')->nullOnDelete();
            $table->string('compliance_type', 100)->index()->comment('Boiler Inspection, Pressure Vessel Testing ...');
            $table->string('inspection_name', 150);
            $table->string('authority', 150)->nullable();
            $table->string('agency', 150)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->unsignedInteger('frequency_days')->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->date('next_due_date')->nullable()->index();
            $table->string('certificate_no', 100)->nullable();
            $table->string('result', 30)->nullable()->comment('passed | failed | conditional');
            $table->string('status', 20)->default('valid')->index()->comment('valid | due_soon | overdue | failed');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pm_compliance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_id')->constrained('pm_compliances')->cascadeOnDelete();
            $table->date('inspection_date');
            $table->date('next_due_date')->nullable();
            $table->string('certificate_no', 100)->nullable();
            $table->string('agency', 150)->nullable();
            $table->string('result', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pm_calibrations', function (Blueprint $table) {
            $table->id();
            $table->string('instrument_code', 50)->unique();
            $table->string('instrument_name', 150);
            $table->foreignId('asset_id')->nullable()->constrained('pm_assets')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->string('serial_number', 100)->nullable();
            $table->string('agency', 150)->nullable();
            $table->unsignedInteger('frequency_days')->default(365);
            $table->date('last_calibration_date')->nullable();
            $table->date('next_due_date')->nullable()->index();
            $table->string('certificate_no', 100)->nullable();
            $table->string('result', 30)->nullable()->comment('passed | failed');
            $table->string('status', 20)->default('valid')->index()->comment('valid | due_soon | overdue | failed | under_calibration');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pm_calibration_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calibration_id')->constrained('pm_calibrations')->cascadeOnDelete();
            $table->date('calibration_date');
            $table->date('next_due_date')->nullable();
            $table->string('certificate_no', 100)->nullable();
            $table->string('agency', 150)->nullable();
            $table->string('result', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_calibration_records');
        Schema::dropIfExists('pm_calibrations');
        Schema::dropIfExists('pm_compliance_records');
        Schema::dropIfExists('pm_compliances');
    }
};
