<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('period_id')
                  ->constrained('accounting_periods')
                  ->restrictOnDelete();
            $table->string('reference_no', 30)->unique();
            $table->date('date');
            $table->enum('status', ['draft', 'posted', 'voided'])
                  ->default('draft');
            $table->enum('source_type', [
                  'manual',
                  'opening_balance',
                  'adjustment',
                  'reversal',
            ])->default('manual');
            $table->string('summary_text', 255);
            
            // Audit trail
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->foreignId('posted_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            
            // Reversal link
            $table->foreignId('reversed_from_id')
                  ->nullable()
                  ->constrained('journal_headers')
                  ->nullOnDelete();
            
            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'status']);
            $table->index(['period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_headers');
    }
};