<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advance_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_advance_id')->constrained()->cascadeOnDelete();
            $table->enum('settlement_type', ['expense_claim', 'cash_return', 'payroll_deduct']);
            $table->decimal('amount', 15, 2);
            $table->date('settlement_date');
            $table->string('reference_no')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journal_headers');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_settlements');
    }
};