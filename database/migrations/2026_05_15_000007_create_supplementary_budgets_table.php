<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplementary_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annual_budget_id')->constrained()->cascadeOnDelete();

            // No. Anggaran Tambahan, e.g. "AT2026/001"
            $table->string('supplementary_no', 30)->unique();

            $table->string('title', 200);
            $table->text('justification');

            // Status: draft | submitted | approved | rejected | posted
            $table->string('status', 20)->default('draft');

            // Tambahan positif atau negatif (pemotongan)
            $table->decimal('amount', 15, 2)->default(0);

            // Akaun yang terjejas
            $table->foreignId('budget_item_id')->constrained()->cascadeOnDelete();

            // Sumber tambahan: peruntukan_kerajaan | tabung_khas | caruman_agensi
            $table->string('funding_source', 50)->nullable();

            // Tarikh
            $table->date('effective_date');

            // Rujukan dokumen sokongan
            $table->string('supporting_doc', 150)->nullable();

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
        Schema::dropIfExists('supplementary_budgets');
    }
};
