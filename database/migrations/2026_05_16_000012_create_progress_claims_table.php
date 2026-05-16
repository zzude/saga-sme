<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_claims', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            $table->string('claim_number')->unique(); // e.g. PC-2026-00001
            $table->unsignedBigInteger('procurement_contract_id');
            $table->integer('claim_no'); // 1st claim, 2nd claim...
            $table->date('claim_date');

            // Amounts
            $table->decimal('claim_amount', 15, 2);           // amount claimed
            $table->decimal('retention_deduction', 15, 2)->default(0); // wang tahanan dipotong
            $table->decimal('previous_deductions', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2)
                ->storedAs('claim_amount - retention_deduction - previous_deductions');

            // Completion percentage
            $table->decimal('completion_percentage', 5, 2)->nullable(); // % kerja siap

            // Verification
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->date('submitted_date')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();  // SO / Pegawai Penyelia
            $table->date('verified_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('approved_date')->nullable();

            // Payment linkage
            $table->unsignedBigInteger('payment_voucher_id')->nullable(); // link to PV
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();

            // GL posting
            $table->boolean('is_posted')->default(false);
            $table->unsignedBigInteger('journal_id')->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'verified',
                'approved',
                'paid',
                'rejected',
            ])->default('draft');

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('procurement_contract_id')
                ->references('id')->on('procurement_contracts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_claims');
    }
};
