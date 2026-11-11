<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AMC (Annual Maintenance Contract) & vendor performance.
 *
 * pm_amc_contracts  -> the contract
 * pm_amc_assets     -> which assets the contract covers
 * pm_amc_visits     -> scheduled vendor visits (missed visits generate alerts)
 * pm_vendor_services-> every service a vendor performed, with rating inputs for the SLA score
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_amc_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('amc_no', 30)->unique();
            $table->foreignId('vendor_id')->constrained('pm_vendors');
            $table->string('name', 150);
            $table->date('start_date');
            $table->date('end_date')->index();
            $table->decimal('contract_value', 14, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->string('service_frequency', 30)->nullable()->comment('monthly | quarterly | half_yearly | yearly');
            $table->text('scope_of_work')->nullable();
            $table->unsignedInteger('response_time_hours')->nullable();
            $table->unsignedInteger('resolution_time_hours')->nullable();
            $table->text('service_conditions')->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('status', 20)->default('active')->index()->comment('active | expired | terminated');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pm_amc_assets', function (Blueprint $table) {
            $table->foreignId('amc_id')->constrained('pm_amc_contracts')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('pm_assets')->cascadeOnDelete();
            $table->primary(['amc_id', 'asset_id']);
        });

        Schema::create('pm_amc_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amc_id')->constrained('pm_amc_contracts')->cascadeOnDelete();
            $table->date('scheduled_date')->index();
            $table->date('actual_date')->nullable();
            $table->string('status', 20)->default('scheduled')->comment('scheduled | completed | missed');
            $table->text('work_done')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_vendor_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('pm_vendors');
            $table->foreignId('work_order_id')->nullable()->constrained('pm_work_orders')->nullOnDelete();
            $table->foreignId('amc_id')->nullable()->constrained('pm_amc_contracts')->nullOnDelete();
            $table->date('service_date');
            $table->decimal('response_hours', 8, 2)->nullable();
            $table->decimal('resolution_hours', 8, 2)->nullable();
            $table->boolean('response_within_sla')->default(true);
            $table->boolean('resolution_within_sla')->default(true);
            $table->unsignedTinyInteger('work_quality')->default(3)->comment('1 to 5');
            $table->boolean('attended')->default(true);
            $table->boolean('repeat_failure')->default(false);
            $table->decimal('cost', 14, 2)->default(0);
            $table->text('feedback')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_vendor_services');
        Schema::dropIfExists('pm_amc_visits');
        Schema::dropIfExists('pm_amc_assets');
        Schema::dropIfExists('pm_amc_contracts');
    }
};
