<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code')->nullable();              // user-defined code e.g. ITM-001
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'product',
                'service',
                'bundle',
            ])->default('product');

            // Pricing
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->string('unit_of_measure')->default('unit');  // unit, kg, liter, hour, etc.

            // SST
            $table->boolean('is_sst_applicable')->default(false);
            $table->decimal('sst_rate', 5, 2)->default(8.00);

            // Inventory tracking
            $table->boolean('track_inventory')->default(true);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 2)->default(0);

            // GL Account linkage
            $table->unsignedBigInteger('income_account_id')->nullable();   // default income account
            $table->unsignedBigInteger('expense_account_id')->nullable();  // COGS account

            // Category (simple string, no separate table needed)
            $table->string('category')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'code'], 'unique_company_item_code');
            $table->index(['company_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
