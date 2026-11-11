<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-cutting tables.
 *
 * pm_attachments   -> one polymorphic table for every uploaded document / photo.
 *                     attachable_type + attachable_id point to the owning record
 *                     (asset, request, work order, PM log, compliance ...).
 *                     "category" tells what kind of document it is (manual, before_photo ...).
 * pm_history       -> audit trail. Every status change and important edit writes a row here.
 * pm_notifications -> in-app notifications. dedupe_key stops schedulers creating duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 100);
            $table->unsignedBigInteger('attachable_id');
            $table->string('category', 50)->default('general')->index();
            $table->string('title', 150)->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
        });

        Schema::create('pm_history', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 100);
            $table->unsignedBigInteger('model_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->index()->comment('created | updated | status_changed | approved | rejected ...');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->json('changes')->nullable()->comment('Old/new values for important edits');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['model_type', 'model_id']);
        });

        Schema::create('pm_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('title', 150);
            $table->text('message')->nullable();
            $table->string('link')->nullable();
            $table->string('level', 20)->default('info')->comment('info | warning | danger | success');
            $table->string('dedupe_key', 150)->nullable()->unique();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_notifications');
        Schema::dropIfExists('pm_history');
        Schema::dropIfExists('pm_attachments');
    }
};
