<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('advance_no', 20)->unique();
            $table->string('purpose');
            $table->decimal('amount_requested', 15, 2);
            $table->decimal('amount_approved', 15, 2)->nullable();
            $table->decimal('amount_settled', 15, 2)->default(0);
            $table->enum('status', ['draft', 'approved', 'disbursed', 'settled', 'cancelled'])->default('draft');
            $table->date('applied_date');
            $table->date('approved_date')->nullable();
            $table->date('disbursed_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->foreignId('journal_id')->nullable()->constrained('journal_headers');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('disbursed_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};