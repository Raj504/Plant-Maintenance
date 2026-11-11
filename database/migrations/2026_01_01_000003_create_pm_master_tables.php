<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master / reference data used by every other module.
 *
 * pm_plants       -> plant / unit
 * pm_departments  -> departments inside a plant
 * pm_categories   -> generic categories (asset, maintenance, spare, problem, permit, compliance)
 * pm_shifts       -> factory shifts (A, B, C ...)
 * pm_vendors      -> external maintenance vendors / AMC vendors
 * pm_teams        -> maintenance teams (Electrical, Mechanical ...)
 * pm_team_user    -> members of each team
 * pm_settings     -> key/value module configuration
 * pm_sequences    -> document number counters (MR-2026-0001 etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_plants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pm_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->nullable()->constrained('pm_plants')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('pm_departments')->nullOnDelete();
        });

        Schema::create('pm_categories', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index()->comment('asset | maintenance | spare | problem | permit | compliance');
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'name']);
        });

        Schema::create('pm_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pm_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number', 30)->nullable();
            $table->string('services')->nullable()->comment('What the vendor does');
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('lead_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pm_team_user', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained('pm_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['team_id', 'user_id']);
        });

        Schema::create('pm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('label', 150)->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        Schema::create('pm_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->unsignedInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
        Schema::dropIfExists('pm_sequences');
        Schema::dropIfExists('pm_settings');
        Schema::dropIfExists('pm_team_user');
        Schema::dropIfExists('pm_teams');
        Schema::dropIfExists('pm_vendors');
        Schema::dropIfExists('pm_shifts');
        Schema::dropIfExists('pm_categories');
        Schema::dropIfExists('pm_departments');
        Schema::dropIfExists('pm_plants');
    }
};
