<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('name', 50);
                  // contoh: "Jan 2026", "FY2026 Q1"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])
                  ->default('open');
            $table->foreignId('closed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Periods dalam company tidak boleh overlap
            $table->index(['company_id', 'start_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};