<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maintenance Request / Complaint workflow.
 *
 * pm_requests                     -> the complaint itself. "status" walks through the sequence
 *                                    submitted -> inspection_pending -> assessment_pending ->
 *                                    approval_pending -> approved -> work_order_created -> ... -> closed
 * pm_request_inspections          -> step 2: inspector report (many allowed on re-submission)
 * pm_technical_assessments        -> step 3: technical expert estimate
 * pm_technical_assessment_spares  -> spare parts the expert says are required
 * pm_approvals                    -> step 4: every approve / reject / send-back decision (never overwritten)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 30)->unique();
            $table->dateTime('request_date')->index();
            $table->foreignId('department_id')->nullable()->constrained('pm_departments')->nullOnDelete();
            $table->foreignId('asset_id')->constrained('pm_assets');
            $table->foreignId('component_id')->nullable()->constrained('pm_assets')->nullOnDelete();
            $table->string('request_type', 40)->index()->comment('breakdown | corrective | preventive | improvement | inspection | replacement | electrical | mechanical | utility');
            $table->string('priority', 20)->default('medium')->index()->comment('critical | high | medium | low');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('shift_id')->nullable()->constrained('pm_shifts')->nullOnDelete();
            $table->text('problem_description');
            $table->foreignId('problem_category_id')->nullable()->constrained('pm_categories')->nullOnDelete();
            $table->boolean('machine_stopped')->default(false);
            $table->boolean('production_affected')->default(false);
            $table->text('remarks')->nullable();
            $table->string('status', 40)->default('submitted')->index();
            // Emergency path: skips the sequence but records who authorised it
            $table->boolean('is_emergency')->default(false);
            $table->text('emergency_reason')->nullable();
            $table->foreignId('emergency_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('emergency_approved_at')->nullable();
            $table->boolean('emergency_reviewed')->default(false)->comment('Retrospective review done');
            $table->text('emergency_review_remarks')->nullable();
            // Denormalised figures so lists and reports are fast
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->decimal('approved_cost', 14, 2)->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closure_remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pm_request_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('pm_requests')->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users');
            $table->dateTime('inspection_date');
            $table->string('machine_condition', 100)->nullable();
            $table->text('actual_problem');
            $table->text('root_cause')->nullable();
            $table->boolean('machine_running')->default(false);
            $table->string('safety_risk', 20)->default('none')->comment('none | low | medium | high');
            $table->string('production_impact', 20)->default('none')->comment('none | partial | full');
            $table->text('temporary_solution')->nullable();
            $table->text('required_action')->nullable();
            $table->text('remarks')->nullable();
            $table->string('recommendation', 20)->default('proceed')->comment('proceed | reject');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_technical_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('pm_requests')->cascadeOnDelete();
            $table->foreignId('assessed_by')->constrained('users');
            $table->dateTime('assessment_date');
            $table->text('failure_nature');
            $table->text('probable_root_cause')->nullable();
            $table->string('action_required', 20)->default('repair')->comment('repair | replace | both');
            $table->string('required_manpower', 150)->nullable();
            $table->boolean('external_vendor_required')->default(false);
            $table->foreignId('vendor_id')->nullable()->constrained('pm_vendors')->nullOnDelete();
            $table->decimal('estimated_downtime_hours', 8, 2)->default(0);
            $table->decimal('estimated_labour_cost', 14, 2)->default(0);
            $table->decimal('estimated_material_cost', 14, 2)->default(0);
            $table->decimal('estimated_service_cost', 14, 2)->default(0);
            $table->decimal('total_estimated_cost', 14, 2)->default(0);
            $table->boolean('permit_required')->default(false)->comment('Needs PTW before work starts');
            $table->text('recommendation')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_technical_assessment_spares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('pm_technical_assessments')->cascadeOnDelete();
            $table->unsignedBigInteger('spare_id')->nullable()->comment('FK added in inventory migration');
            $table->string('spare_name', 150)->nullable()->comment('Free text when spare is not in master');
            $table->decimal('quantity', 10, 2);
            $table->decimal('estimated_cost', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pm_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('pm_requests')->cascadeOnDelete();
            $table->string('stage', 30)->default('budget')->comment('budget | emergency_review');
            $table->foreignId('approver_id')->constrained('users');
            $table->string('action', 20)->index()->comment('approved | rejected | sent_back');
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->decimal('approved_cost', 14, 2)->nullable();
            $table->text('remarks');
            $table->dateTime('acted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_approvals');
        Schema::dropIfExists('pm_technical_assessment_spares');
        Schema::dropIfExists('pm_technical_assessments');
        Schema::dropIfExists('pm_request_inspections');
        Schema::dropIfExists('pm_requests');
    }
};
