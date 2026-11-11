<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends Laravel's default users table with the employee details the
 * maintenance module needs (employee code, department, designation, phone).
 * We extend instead of creating a separate user table so the existing
 * Laravel authentication keeps working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 50)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('department_id')->nullable()->after('email')->index();
            $table->string('designation', 100)->nullable()->after('department_id');
            $table->string('phone', 30)->nullable()->after('designation');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'department_id', 'designation', 'phone', 'avatar', 'is_active']);
        });
    }
};
