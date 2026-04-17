<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('invoice_no', 30);
            $table->date('date');
            $table->date('due_date');
            $table->enum('status', ['draft','sent','partial','paid','overdue','void'])->default('draft');
            $table->char('currency_code', 3)->default('MYR');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'invoice_no']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'due_date']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};