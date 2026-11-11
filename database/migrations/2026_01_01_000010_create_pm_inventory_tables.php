<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spare parts & inventory.
 *
 * pm_spares             -> spare part master. current_stock is a cached figure that is ALWAYS
 *                          recalculated from the ledger inside StockLedgerService (never edited by hand).
 * pm_stock_transactions -> the ledger. Every movement is a row. Rows are never deleted.
 * pm_asset_bom          -> Bill of Materials: which spares a machine uses.
 * pm_purchase_requests  -> maintenance purchase requisition raised when stock is short.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_spares', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained('pm_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('uom', 20)->default('Nos')->comment('Unit of measurement');
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0)->comment('Cached; recalculated from ledger');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->foreignId('preferred_vendor_id')->nullable()->constrained('pm_vendors')->nullOnDelete();
            $table->string('store_location', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pm_technical_assessment_spares', function (Blueprint $table) {
            $table->foreign('spare_id')->references('id')->on('pm_spares')->nullOnDelete();
        });

        Schema::create('pm_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no', 30)->unique();
            $table->foreignId('spare_id')->constrained('pm_spares');
            $table->string('type', 20)->index()->comment('opening | receipt | issue | return | adjustment_in | adjustment_out');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->date('transaction_date')->index();
            $table->string('reference_no', 100)->nullable();
            $table->foreignId('work_order_id')->nullable()->constrained('pm_work_orders')->nullOnDelete();
            $table->foreignId('issue_transaction_id')->nullable()->constrained('pm_stock_transactions')->nullOnDelete()->comment('For returns: the original issue');
            $table->decimal('balance_after', 12, 2)->default(0)->comment('Stock balance after this movement');
            $table->foreignId('user_id')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_asset_bom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('pm_assets')->cascadeOnDelete();
            $table->foreignId('spare_id')->constrained('pm_spares')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->string('criticality', 20)->default('medium');
            $table->foreignId('preferred_vendor_id')->nullable()->constrained('pm_vendors')->nullOnDelete();
            $table->unsignedInteger('replacement_interval_days')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['asset_id', 'spare_id']);
        });

        Schema::create('pm_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no', 30)->unique();
            $table->foreignId('spare_id')->constrained('pm_spares');
            $table->foreignId('request_id')->nullable()->constrained('pm_requests')->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('pm_work_orders')->nullOnDelete();
            $table->decimal('required_qty', 12, 2);
            $table->decimal('available_stock', 12, 2)->default(0);
            $table->decimal('shortage_qty', 12, 2)->default(0);
            $table->date('required_by_date')->nullable();
            $table->string('status', 20)->default('pending')->index()->comment('pending | approved | ordered | received | cancelled');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('vendor_id')->nullable()->constrained('pm_vendors')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_purchase_requests');
        Schema::dropIfExists('pm_asset_bom');
        Schema::dropIfExists('pm_stock_transactions');
        Schema::table('pm_technical_assessment_spares', function (Blueprint $table) {
            $table->dropForeign(['spare_id']);
        });
        Schema::dropIfExists('pm_spares');
    }
};
