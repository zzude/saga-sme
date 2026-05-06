<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('loan_no', 20)->unique();
            $table->unsignedBigInteger('employee_id');

            $table->enum('loan_type', ['personal', 'emergency', 'festival']);
            $table->decimal('amount_applied', 15, 2);
            $table->decimal('amount_approved', 15, 2)->nullable();

            $table->decimal('interest_rate', 5, 2)->default(0.00);
            $table->unsignedTinyInteger('tenure_months');

            $table->enum('status', ['draft', 'approved', 'disbursed', 'settled', 'rejected'])->default('draft');

            $table->date('applied_date');
            $table->date('approved_date')->nullable();
            $table->date('disbursed_date')->nullable();

            $table->unsignedBigInteger('account_id');           // 1180 Staff Loans Receivable
            $table->unsignedBigInteger('disbursement_account_id'); // Bank account used

            $table->unsignedBigInteger('journal_id')->nullable(); // Disbursement journal

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('disbursement_account_id')->references('id')->on('accounts');
            $table->foreign('journal_id')->references('id')->on('journal_headers');
            $table->foreign('approved_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_loans');
    }
};
