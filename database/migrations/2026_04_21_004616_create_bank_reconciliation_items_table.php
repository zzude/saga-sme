<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->foreignId('statement_line_id')->nullable()->constrained('bank_statement_lines')->nullOnDelete();
            $table->enum('source_type', ['journal', 'statement_only', 'adjustment'])->default('journal');
            $table->enum('status', ['pending', 'cleared', 'outstanding'])->default('pending');
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();

            $table->index(['reconciliation_id']);
            $table->index(['journal_line_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
    }
};