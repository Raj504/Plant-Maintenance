<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safety: Permit To Work (PTW) and Lockout / Tagout (LOTO).
 *
 * pm_safety_permits -> one permit per hazardous job, linked to the work order.
 * pm_loto_records   -> each energy isolation done under that permit. A permit cannot be
 *                      closed until every LOTO record has been de-isolated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_safety_permits', function (Blueprint $table) {
            $table->id();
            $table->string('permit_no', 30)->unique();
            $table->foreignId('work_order_id')->nullable()->constrained('pm_work_orders')->nullOnDelete();
            $table->foreignId('asset_id')->constrained('pm_assets');
            $table->string('work_location', 150)->nullable();
            $table->text('work_description');
            $table->string('permit_type', 40)->index()->comment('electrical | hot_work | height | confined_space | heavy_equipment | hazardous_area | general');
            $table->text('hazards_identified')->nullable();
            $table->text('safety_precautions')->nullable();
            $table->text('ppe_requirements')->nullable();
            $table->foreignId('issued_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->string('status', 20)->default('draft')->index()->comment('draft | issued | active | closed | cancelled');
            $table->text('closure_remarks')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_loto_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained('pm_safety_permits')->cascadeOnDelete();
            $table->string('energy_source', 100)->comment('Electrical, Hydraulic, Pneumatic, Steam ...');
            $table->string('isolation_point', 150);
            $table->string('lock_number', 50)->nullable();
            $table->string('tag_number', 50)->nullable();
            $table->dateTime('isolated_at');
            $table->foreignId('isolated_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('deisolated_at')->nullable();
            $table->foreignId('deisolated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_loto_records');
        Schema::dropIfExists('pm_safety_permits');
    }
};
