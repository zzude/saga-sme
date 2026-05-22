<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('session_id');
            $table->string('transaction_no')->unique();     // POS-2026-00001

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->enum('payment_method', [
                'cash',
                'card',
                'qr',        // DuitNow QR
                'credit',    // store credit / tab
                'mixed',     // multiple payment methods
            ])->default('cash');

            $table->decimal('amount_tendered', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);

            $table->string('customer_name')->nullable();    // walk-in customer
            $table->text('notes')->nullable();

            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->text('void_reason')->nullable();

            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('pos_sessions')->onDelete('cascade');
            $table->foreign('journal_id')->references('id')->on('journal_headers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['company_id', 'status', 'created_at']);
        });

        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('item_id')->nullable();

            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->boolean('is_sst_applicable')->default(false);
            $table->decimal('sst_rate', 5, 2)->default(8);
            $table->decimal('sst_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('pos_transactions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_items');
        Schema::dropIfExists('pos_transactions');
    }
};
