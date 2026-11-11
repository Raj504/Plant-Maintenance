<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work Orders.
 *
 * pm_work_orders              -> created automatically after approval (or from a PM schedule / emergency)
 * pm_work_order_tasks         -> small task list the technician ticks off
 * pm_work_order_labour        -> labour hours per person per day
 * pm_work_order_verifications -> supervisor verification before closure (approve or send back)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_no', 30)->unique();
            $table->foreignId('request_id')->nullable()->constrained('pm_requests')->nullOnDelete();
            $table->foreignId('asset_id')->constrained('pm_assets');
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->string('source', 20)->default('request')->index()->comment('request | preventive | emergency | manual');
            $table->string('work_type', 40)->index()->comment('breakdown | corrective | preventive | ...');
            $table->string('priority', 20)->default('medium')->index();
            $table->foreignId('team_id')->nullable()->constrained('pm_teams')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable()->index();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->text('description');
            $table->decimal('estimated_cost', 14, 2)->default(0);
            $table->decimal('actual_cost', 14, 2)->default(0);
            $table->decimal('labour_hours', 8, 2)->default(0);
            $table->decimal('downtime_hours', 8, 2)->default(0);
            $table->text('safety_requirements')->nullable();
            $table->boolean('permit_required')->default(false);
            $table->string('status', 30)->default('open')->index()->comment('open | assigned | in_progress | on_hold | completed | verification_pending | closed | cancelled');
            $table->text('hold_reason')->nullable();
            // Execution details filled by technician
            $table->text('actual_problem_found')->nullable();
            $table->text('work_performed')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('pm_vendors')->nullOnDelete();
            $table->string('vendor_details')->nullable();
            $table->text('repair_remarks')->nullable();
            // Closure
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->string('final_machine_status', 30)->nullable();
            $table->text('final_remarks')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pm_work_order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('pm_work_orders')->cascadeOnDelete();
            $table->string('description');
            $table->boolean('is_done')->default(false);
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('done_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_work_order_labour', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('pm_work_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('work_date');
            $table->decimal('hours', 6, 2);
            $table->decimal('rate', 10, 2)->default(0)->comment('Hourly rate used for cost');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_work_order_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('pm_work_orders')->cascadeOnDelete();
            $table->foreignId('verified_by')->constrained('users');
            $table->dateTime('verification_date');
            $table->boolean('machine_operation_ok')->default(false);
            $table->boolean('safety_ok')->default(false);
            $table->boolean('repair_quality_ok')->default(false);
            $table->boolean('production_ready')->default(false);
            $table->text('test_results')->nullable();
            $table->text('observations')->nullable();
            $table->string('decision', 20)->comment('approved | sent_back');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_work_order_verifications');
        Schema::dropIfExists('pm_work_order_labour');
        Schema::dropIfExists('pm_work_order_tasks');
        Schema::dropIfExists('pm_work_orders');
    }
};
