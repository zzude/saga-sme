<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. statutory_rate_versions ────────────────────────────────────
        // KWSP/SOCSO/EIS rates by year — versioned, never hardcoded
        Schema::create('statutory_rate_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->enum('type', [
                'KWSP_EE', 'KWSP_ER',
                'SOCSO_EE', 'SOCSO_ER',
                'EIS_EE', 'EIS_ER',
            ]);
            $table->decimal('rate', 8, 4)->comment('e.g. 11.0000 = 11%');
            $table->decimal('ceiling_salary', 15, 2)->nullable()->comment('Max salary for contribution');
            $table->decimal('ceiling_amount', 15, 2)->nullable()->comment('Max contribution amount');
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['year', 'type']);
        });

        // ── 2. pcb_brackets ───────────────────────────────────────────────
        // LHDN PCB/MTD table — table-based, not % formula
        Schema::create('pcb_brackets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->enum('marital_status', ['single', 'married_spouse_working', 'married_spouse_not_working']);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->decimal('income_from', 15, 2);
            $table->decimal('income_to', 15, 2)->nullable()->comment('null = no upper limit');
            $table->decimal('base_tax', 15, 2)->default(0);
            $table->decimal('marginal_rate', 8, 4)->comment('Rate on excess above income_from');
            $table->timestamps();

            $table->index(['year', 'marital_status', 'children_count']);
        });

        // ── 3. employees ──────────────────────────────────────────────────
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('employee_no', 20)->comment('e.g. EMP-0001');
            $table->string('name');
            $table->string('ic_no', 20)->nullable()->comment('MyKad / Passport');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('date_joined');
            $table->date('date_resigned')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            $table->decimal('basic_salary', 15, 2)->default(0);
            // Statutory info
            $table->string('epf_no', 20)->nullable();
            $table->string('socso_no', 20)->nullable();
            $table->string('income_tax_no', 20)->nullable();
            $table->enum('marital_status', ['single', 'married_spouse_working', 'married_spouse_not_working'])->default('single');
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->boolean('is_active')->default(true);
            // Bank info for payment
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'employee_no']);
        });

        // ── 4. payroll_periods ────────────────────────────────────────────
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50)->comment('e.g. January 2026');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date')->nullable()->comment('Scheduled payment date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month']);
        });

        // ── 5. payroll_runs ───────────────────────────────────────────────
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->comment('GL accounting period');
            $table->string('reference_no', 30)->comment('e.g. PR-2026-01');
            $table->enum('status', ['draft', 'approved', 'posted', 'locked'])->default('draft');
            // Totals (GL truth — frozen on post)
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_employee_deduction', 15, 2)->default(0);
            $table->decimal('total_employer_cost', 15, 2)->default(0);
            $table->decimal('total_net_salary', 15, 2)->default(0);
            $table->decimal('total_kwsp', 15, 2)->default(0);
            $table->decimal('total_socso', 15, 2)->default(0);
            $table->decimal('total_eis', 15, 2)->default(0);
            $table->decimal('total_pcb', 15, 2)->default(0);
            // Journal link
            $table->foreignId('journal_header_id')->nullable()->constrained('journal_headers')->nullOnDelete();
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('reversal_of_run_id')->nullable()->constrained('payroll_runs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'payroll_period_id']);
        });

        // ── 6. payroll_lines ──────────────────────────────────────────────
        // One row per employee per run — accounting truth
        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Salary components
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0)->comment('Total allowances (future)');
            $table->decimal('gross_salary', 15, 2)->default(0)->comment('basic + allowances');
            // Deduction/cost totals (frozen on post — GL source)
            $table->decimal('total_employee_deduction', 15, 2)->default(0)->comment('KWSP_EE + SOCSO_EE + EIS_EE + PCB');
            $table->decimal('total_employer_cost', 15, 2)->default(0)->comment('gross + KWSP_ER + SOCSO_ER + EIS_ER');
            $table->decimal('net_salary', 15, 2)->default(0)->comment('gross - total_employee_deduction');
            // Audit snapshot
            $table->unsignedSmallInteger('stat_year')->comment('Statutory year used for calculation');
            $table->enum('marital_status', ['single', 'married_spouse_working', 'married_spouse_not_working']);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        // ── 7. payroll_line_deductions ────────────────────────────────────
        // Normalized per component — flexible, auditable
        Schema::create('payroll_line_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_line_id')->constrained('payroll_lines')->cascadeOnDelete();
            $table->enum('component', [
                'KWSP_EE',   // Employee EPF 11%
                'KWSP_ER',   // Employer EPF 13%/12%
                'SOCSO_EE',  // Employee SOCSO 0.5%
                'SOCSO_ER',  // Employer SOCSO 1.75%
                'EIS_EE',    // Employee EIS 0.2%
                'EIS_ER',    // Employer EIS 0.2%
                'PCB',       // Income Tax MTD
            ]);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('rate_used', 8, 4)->nullable()->comment('Snapshot of rate at time of calculation');
            $table->boolean('ceiling_applied')->default(false)->comment('True if ceiling was hit');
            $table->decimal('taxable_income', 15, 2)->nullable()->comment('For PCB — annual taxable income used');
            $table->timestamps();

            $table->unique(['payroll_line_id', 'component']);
        });

        // ── 8. payroll_gl_mappings ────────────────────────────────────────
        // Per-company GL account mapping for payroll components
        Schema::create('payroll_gl_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('component', [
                'SALARY_EXPENSE',
                'EMPLOYER_CONTRIBUTION_EXPENSE',
                'KWSP_PAYABLE',
                'SOCSO_PAYABLE',
                'EIS_PAYABLE',
                'PCB_PAYABLE',
                'NET_SALARY_PAYABLE',
            ]);
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'component']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_gl_mappings');
        Schema::dropIfExists('payroll_line_deductions');
        Schema::dropIfExists('payroll_lines');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('pcb_brackets');
        Schema::dropIfExists('statutory_rate_versions');
    }
};
