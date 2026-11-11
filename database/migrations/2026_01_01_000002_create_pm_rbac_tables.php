<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role Based Access Control (RBAC).
 *
 * pm_roles            -> e.g. Maintenance Supervisor, Technician
 * pm_permissions      -> granular permission keys e.g. pm.assets.view
 * pm_permission_role  -> which permissions each role has
 * pm_role_user        -> which roles each user has (a user may have many roles)
 *
 * Permissions are checked on the server side through the "permission"
 * middleware and Gate::before() in AppServiceProvider.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('System roles cannot be deleted');
            $table->timestamps();
        });

        Schema::create('pm_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('e.g. pm.assets.view');
            $table->string('module', 50)->index();
            $table->string('label', 150);
            $table->timestamps();
        });

        Schema::create('pm_permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('pm_permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('pm_roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('pm_role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('pm_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_role_user');
        Schema::dropIfExists('pm_permission_role');
        Schema::dropIfExists('pm_permissions');
        Schema::dropIfExists('pm_roles');
    }
};
