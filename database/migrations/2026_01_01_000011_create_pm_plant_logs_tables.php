<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily plant logs, utilities and shift handover.
 *
 * pm_log_types       -> configurable log forms. "fields" is a JSON list of extra readings
 *                       e.g. [{"key":"ph","label":"pH","unit":"","type":"number"}].
 *                       Seeded with: Equipment Log, Fuel Register, Energy Register, ECU/AHU Log, Water/ETP Register.
 * pm_daily_logs      -> one entry per equipment per shift. Extra readings stored in "readings" JSON.
 * pm_shift_handovers -> handover notes between shifts; receiver must acknowledge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_log_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->json('fields')->nullable()->comment('Extra configurable readings');
            $table->boolean('has_meter_readings')->default(true)->comment('Show opening/closing/consumption');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pm_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_no', 30)->unique();
            $table->foreignId('log_type_id')->constrained('pm_log_types');
            $table->date('log_date')->index();
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('pm_assets')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('pm_shifts')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_reading', 14, 2)->nullable();
            $table->decimal('closing_reading', 14, 2)->nullable();
            $table->decimal('consumption', 14, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->json('readings')->nullable()->comment('Values for the log type extra fields');
            $table->text('observations')->nullable();
            $table->text('abnormal_conditions')->nullable();
            $table->boolean('is_abnormal')->default(false)->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['log_type_id', 'log_date']);
        });

        Schema::create('pm_shift_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_no', 30)->unique();
            $table->date('handover_date')->index();
            $table->foreignId('from_shift_id')->constrained('pm_shifts');
            $table->foreignId('to_shift_id')->constrained('pm_shifts');
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->foreignId('handed_over_by')->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('pending_work_orders')->nullable();
            $table->text('breakdown_status')->nullable();
            $table->text('machines_under_observation')->nullable();
            $table->text('temporary_repairs')->nullable();
            $table->text('safety_issues')->nullable();
            $table->text('spare_shortage')->nullable();
            $table->text('instructions')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('pending')->index()->comment('pending | acknowledged');
            $table->dateTime('acknowledged_at')->nullable();
            $table->text('acknowledgement_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_shift_handovers');
        Schema::dropIfExists('pm_daily_logs');
        Schema::dropIfExists('pm_log_types');
    }
};
