<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('staff_loan_id');

            $table->unsignedTinyInteger('installment_no');
            $table->date('due_date');

            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);

            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->date('paid_date')->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();

            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');

            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('payroll_run_id')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('staff_loan_id')->references('id')->on('staff_loans');
            $table->foreign('journal_id')->references('id')->on('journal_headers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_loan_repayments');
    }
};
