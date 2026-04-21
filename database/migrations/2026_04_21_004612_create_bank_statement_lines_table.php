<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->date('txn_date');
            $table->string('reference_no', 50)->nullable();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('running_balance', 15, 2)->nullable();
            $table->foreignId('matched_journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->enum('status', ['unmatched', 'matched'])->default('unmatched');
            $table->timestamps();

            $table->index(['reconciliation_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};