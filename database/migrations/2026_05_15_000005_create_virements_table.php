<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annual_budget_id')->constrained()->cascadeOnDelete();

            // No. Viremen, e.g. "VIR2026/001"
            $table->string('virement_no', 30)->unique();

            $table->string('title', 200);
            $table->text('justification')->nullable();

            // Status: draft | pending_approval | approved | rejected | posted
            $table->string('status', 20)->default('draft');

            // Jumlah viremen
            $table->decimal('total_amount', 15, 2)->default(0);

            // Tarikh
            $table->date('virement_date');

            // Rujukan surat kelulusan
            $table->string('approval_reference', 100)->nullable();

            // Approval
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'annual_budget_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virements');
    }
};
