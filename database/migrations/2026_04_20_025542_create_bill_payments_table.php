<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash','transfer','cheque','online'])->default('transfer');
            $table->string('reference_no', 50)->nullable();
            $table->foreignId('bank_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('journal_header_id')->nullable()->constrained('journal_headers')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->string('paid_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'bill_id']);
            $table->index(['company_id', 'payment_date']);
            $table->index(['journal_header_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};