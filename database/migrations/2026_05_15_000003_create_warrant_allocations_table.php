<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warrant_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annual_budget_id')->constrained()->cascadeOnDelete();

            // No. Waran, e.g. "W2026/003"
            $table->string('warrant_no', 30)->unique();

            // Jenis waran: peruntukan_asal | peruntukan_tambahan | pindahan
            $table->string('warrant_type', 30)->default('peruntukan_asal');

            $table->string('title', 200);
            $table->text('description')->nullable();

            // Status: draft | issued | active | exhausted | cancelled
            $table->string('status', 20)->default('draft');

            // Jumlah keseluruhan waran ini
            $table->decimal('total_amount', 15, 2)->default(0);

            // Jumlah dah digunakan
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);

            // Tarikh waran dikeluarkan
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Rujukan surat/dokumen
            $table->string('reference_doc', 100)->nullable();

            // Approval
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('warrant_allocations');
    }
};
