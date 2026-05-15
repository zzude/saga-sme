<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // e.g. "BP2026/001"
            $table->string('budget_no', 30)->unique();

            // Tahun kewangan
            $table->unsignedSmallInteger('financial_year');

            // Tajuk bajet
            $table->string('title', 200);

            // Nota / keterangan
            $table->text('description')->nullable();

            // Status: draft | submitted | approved | active | closed
            $table->string('status', 20)->default('draft');

            // Jumlah keseluruhan (dikira semula dari budget_items)
            $table->decimal('total_amount', 15, 2)->default(0);

            // Baki terkini selepas waran & viremen
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);

            // Tarikh kuatkuasa
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Approval chain
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'financial_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_budgets');
    }
};
