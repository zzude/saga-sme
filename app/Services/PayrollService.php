<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\Employee;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use App\Models\PayrollGlMapping;
use App\Models\PayrollLine;
use App\Models\PayrollLineDeduction;
use App\Models\PayrollRun;
use App\Models\StatutoryRateVersion;
use App\Models\PcbBracket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    // ── 1. Generate Lines ─────────────────────────────────────────────────
    /**
     * Pull all active employees for a company and create payroll_lines.
     * Safe to re-run — skips employees already in the run.
     */
    public function generateLines(PayrollRun $run): int
    {
        if (!$run->isDraft()) {
            throw new \Exception('Can only generate lines for draft payroll runs.');
        }

        $year = $run->payrollPeriod->year;

        $employees = Employee::where('company_id', $run->company_id)
            ->where('is_active', true)
            ->whereNull('date_resigned')
            ->get();

        if ($employees->isEmpty()) {
            throw new \Exception('No active employees found for this company.');
        }

        $generated = 0;

        foreach ($employees as $employee) {
            // Skip if line already exists
            $exists = PayrollLine::where('payroll_run_id', $run->id)
                ->where('employee_id', $employee->id)
                ->exists();

            if ($exists) continue;

            // Create line with calculated amounts
            $line = $this->calculateLine($run, $employee, $year);
            $generated++;
        }

        // Update run totals
        $this->recalculateRunTotals($run);

        Log::info('[Payroll] Lines generated', [
            'run_id'    => $run->id,
            'generated' => $generated,
        ]);

        return $generated;
    }

    // ── 2. Calculate Line ─────────────────────────────────────────────────
    /**
     * Calculate all statutory deductions for one employee.
     * Returns the saved PayrollLine.
     */
    public function calculateLine(PayrollRun $run, Employee $employee, int $year): PayrollLine
    {
        $grossSalary = (float) $employee->basic_salary;

        // ── KWSP / EPF ────────────────────────────────────────────────────
        [$kwspEe, $kwspEeRate, $kwspEeCeiling] = $this->calcKwspEmployee($grossSalary, $year);
        [$kwspEr, $kwspErRate, $kwspErCeiling] = $this->calcKwspEmployer($grossSalary, $year);

        // ── SOCSO ─────────────────────────────────────────────────────────
        [$socsoEe, $socsoEeRate, $socsoEeCeiling] = $this->calcSocso($grossSalary, $year, 'SOCSO_EE');
        [$socsoEr, $socsoErRate, $socsoErCeiling] = $this->calcSocso($grossSalary, $year, 'SOCSO_ER');

        // ── EIS ───────────────────────────────────────────────────────────
        [$eisEe, $eisEeRate, $eisEeCeiling] = $this->calcEis($grossSalary, $year, 'EIS_EE');
        [$eisEr, $eisErRate, $eisErCeiling] = $this->calcEis($grossSalary, $year, 'EIS_ER');

        // ── PCB / MTD ─────────────────────────────────────────────────────
        [$pcb, $annualTaxable] = $this->calcPcb(
            $grossSalary,
            $year,
            $employee->marital_status,
            $employee->children_count
        );

        // ── Totals ────────────────────────────────────────────────────────
        $totalEmployeeDeduction = $kwspEe + $socsoEe + $eisEe + $pcb;
        $netSalary              = $grossSalary - $totalEmployeeDeduction;
        $totalEmployerCost      = $grossSalary + $kwspEr + $socsoEr + $eisEr;

        // ── Save PayrollLine ──────────────────────────────────────────────
        $line = PayrollLine::create([
            'payroll_run_id'           => $run->id,
            'employee_id'              => $employee->id,
            'basic_salary'             => $grossSalary,
            'allowances'               => 0,
            'gross_salary'             => $grossSalary,
            'total_employee_deduction' => round($totalEmployeeDeduction, 2),
            'total_employer_cost'      => round($totalEmployerCost, 2),
            'net_salary'               => round($netSalary, 2),
            'stat_year'                => $year,
            'marital_status'           => $employee->marital_status,
            'children_count'           => $employee->children_count,
        ]);

        // ── Save deductions (normalized) ──────────────────────────────────
        $deductions = [
            ['component' => 'KWSP_EE',  'amount' => $kwspEe,  'rate' => $kwspEeRate,  'ceiling' => $kwspEeCeiling,  'taxable' => null],
            ['component' => 'KWSP_ER',  'amount' => $kwspEr,  'rate' => $kwspErRate,  'ceiling' => $kwspErCeiling,  'taxable' => null],
            ['component' => 'SOCSO_EE', 'amount' => $socsoEe, 'rate' => $socsoEeRate, 'ceiling' => $socsoEeCeiling, 'taxable' => null],
            ['component' => 'SOCSO_ER', 'amount' => $socsoEr, 'rate' => $socsoErRate, 'ceiling' => $socsoErCeiling, 'taxable' => null],
            ['component' => 'EIS_EE',   'amount' => $eisEe,   'rate' => $eisEeRate,   'ceiling' => $eisEeCeiling,   'taxable' => null],
            ['component' => 'EIS_ER',   'amount' => $eisEr,   'rate' => $eisErRate,   'ceiling' => $eisErCeiling,   'taxable' => null],
            ['component' => 'PCB',      'amount' => $pcb,     'rate' => null,         'ceiling' => false,           'taxable' => $annualTaxable],
        ];

        foreach ($deductions as $d) {
            PayrollLineDeduction::create([
                'payroll_line_id' => $line->id,
                'component'       => $d['component'],
                'amount'          => round($d['amount'], 2),
                'rate_used'       => $d['rate'],
                'ceiling_applied' => $d['ceiling'],
                'taxable_income'  => $d['taxable'],
            ]);
        }

        return $line;
    }

    // ── 3. Post to GL ─────────────────────────────────────────────────────
    /**
     * Post approved payroll run to General Ledger.
     * GL entry pattern:
     *   DR Salary Expense          (total_gross)
     *   DR Employer Contribution   (total KWSP_ER + SOCSO_ER + EIS_ER)
     *   CR KWSP Payable            (total KWSP_EE + KWSP_ER)
     *   CR SOCSO Payable           (total SOCSO_EE + SOCSO_ER)
     *   CR EIS Payable             (total EIS_EE + EIS_ER)
     *   CR PCB Payable             (total PCB)
     *   CR Net Salary Payable      (total_net_salary)
     */
    public function post(PayrollRun $run): void
    {
        if (!$run->isApproved()) {
            throw new \Exception('Only approved payroll runs can be posted.');
        }

        if ($run->journal_header_id) {
            throw new \Exception('Payroll run already posted to GL.');
        }

        if ($run->lines->isEmpty()) {
            throw new \Exception('No payroll lines found. Generate lines first.');
        }

        // Load GL mappings
        $mappings = $this->loadGlMappings($run->company_id);

        DB::transaction(function () use ($run, $mappings) {

            // Recalculate run totals fresh from lines
            $this->recalculateRunTotals($run);
            $run->refresh();

            // Compute employer contribution total (KWSP_ER + SOCSO_ER + EIS_ER)
            $totalKwspEr  = $this->sumDeductionComponent($run, 'KWSP_ER');
            $totalSocsoEr = $this->sumDeductionComponent($run, 'SOCSO_ER');
            $totalEisEr   = $this->sumDeductionComponent($run, 'EIS_ER');
            $totalKwspEe  = $this->sumDeductionComponent($run, 'KWSP_EE');
            $totalSocsoEe = $this->sumDeductionComponent($run, 'SOCSO_EE');
            $totalEisEe   = $this->sumDeductionComponent($run, 'EIS_EE');

            $totalEmployerContrib = $totalKwspEr + $totalSocsoEr + $totalEisEr;
            $totalKwspPayable     = $totalKwspEe + $totalKwspEr;
            $totalSocsoPayable    = $totalSocsoEe + $totalSocsoEr;
            $totalEisPayable      = $totalEisEe + $totalEisEr;

            $period = $run->payrollPeriod;

            // Create Journal Header
            $journal = JournalHeader::create([
                'company_id'   => $run->company_id,
                'period_id'    => $run->period_id,
                'reference_no' => $run->reference_no,
                'date'         => $period->end_date,
                'status'       => 'posted',
                'source_type'  => 'manual',
                'summary_text' => 'Payroll — ' . $period->name,
                'created_by'   => Auth::id(),
                'posted_by'    => Auth::id(),
                'posted_at'    => now(),
            ]);

            // DR Salary Expense (gross salaries)
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $mappings['SALARY_EXPENSE'],
                'debit'             => $run->total_gross,
                'credit'            => 0,
                'description'       => 'Gross Salary — ' . $period->name,
            ]);

            // DR Employer Contribution Expense (KWSP_ER + SOCSO_ER + EIS_ER)
            if ($totalEmployerContrib > 0) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $mappings['EMPLOYER_CONTRIBUTION_EXPENSE'],
                    'debit'             => round($totalEmployerContrib, 2),
                    'credit'            => 0,
                    'description'       => 'Employer Contributions (EPF/SOCSO/EIS) — ' . $period->name,
                ]);
            }

            // CR KWSP Payable (EE + ER combined)
            if ($totalKwspPayable > 0) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $mappings['KWSP_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => round($totalKwspPayable, 2),
                    'description'       => 'EPF Payable (EE ' . round($totalKwspEe, 2) . ' + ER ' . round($totalKwspEr, 2) . ')',
                ]);
            }

            // CR SOCSO Payable (EE + ER combined)
            if ($totalSocsoPayable > 0) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $mappings['SOCSO_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => round($totalSocsoPayable, 2),
                    'description'       => 'SOCSO Payable (EE ' . round($totalSocsoEe, 2) . ' + ER ' . round($totalSocsoEr, 2) . ')',
                ]);
            }

            // CR EIS Payable (EE + ER combined)
            if ($totalEisPayable > 0) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $mappings['EIS_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => round($totalEisPayable, 2),
                    'description'       => 'EIS Payable (EE ' . round($totalEisEe, 2) . ' + ER ' . round($totalEisEr, 2) . ')',
                ]);
            }

            // CR PCB Payable
            if ($run->total_pcb > 0) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $mappings['PCB_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => $run->total_pcb,
                    'description'       => 'PCB/MTD Payable — ' . $period->name,
                ]);
            }

            // CR Net Salary Payable
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $mappings['NET_SALARY_PAYABLE'],
                'debit'             => 0,
                'credit'            => $run->total_net_salary,
                'description'       => 'Net Salary Payable — ' . $period->name,
            ]);

            // Update run — post + lock
            $run->update([
                'status'            => 'posted',
                'journal_header_id' => $journal->id,
                'posted_by'         => Auth::id(),
                'posted_at'         => now(),
            ]);

            Log::info('[Payroll] Posted to GL', [
                'run_id'     => $run->id,
                'journal_id' => $journal->id,
                'total_cost' => $run->total_employer_cost,
            ]);
        });
    }

    // ── 4. Approve ────────────────────────────────────────────────────────
    public function approve(PayrollRun $run): void
    {
        if (!$run->isDraft()) {
            throw new \Exception('Only draft payroll runs can be approved.');
        }

        if ($run->lines->isEmpty()) {
            throw new \Exception('No payroll lines found. Generate lines first.');
        }

        $run->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    // ── Private: Statutory Calculations ──────────────────────────────────

    /** KWSP Employee: 11% of gross, no ceiling on salary */
    private function calcKwspEmployee(float $gross, int $year): array
    {
        $stat = StatutoryRateVersion::getRate($year, 'KWSP_EE');
        if (!$stat) return [0, 0, false];

        $rate   = (float) $stat->rate / 100;
        $amount = round($gross * $rate, 2);

        return [$amount, $stat->rate, false];
    }

    /** KWSP Employer: 13% if salary <= 5000, 12% if > 5000 */
    private function calcKwspEmployer(float $gross, int $year): array
    {
        $stat = StatutoryRateVersion::getRate($year, 'KWSP_ER');
        if (!$stat) return [0, 0, false];

        // 13% if <= ceiling_salary, 12% if above
        $rate = $gross <= (float) $stat->ceiling_salary
            ? (float) $stat->rate / 100        // 13%
            : 12.0 / 100;                       // 12%

        $amount      = round($gross * $rate, 2);
        $ratePercent = $rate * 100;

        return [$amount, $ratePercent, false];
    }

    /** SOCSO: apply ceiling if salary > ceiling_salary */
    private function calcSocso(float $gross, int $year, string $type): array
    {
        $stat = StatutoryRateVersion::getRate($year, $type);
        if (!$stat) return [0, 0, false];

        $ceilingSalary = (float) $stat->ceiling_salary;
        $ceilingAmount = (float) $stat->ceiling_amount;
        $rate          = (float) $stat->rate / 100;
        $ceilingApplied = false;

        if ($gross > $ceilingSalary) {
            // Use ceiling amount directly (SOCSO table-based)
            $amount         = $ceilingAmount;
            $ceilingApplied = true;
        } else {
            $amount = round($gross * $rate, 2);
        }

        return [$amount, $stat->rate, $ceilingApplied];
    }

    /** EIS: same pattern as SOCSO — ceiling based */
    private function calcEis(float $gross, int $year, string $type): array
    {
        $stat = StatutoryRateVersion::getRate($year, $type);
        if (!$stat) return [0, 0, false];

        $ceilingSalary  = (float) $stat->ceiling_salary;
        $ceilingAmount  = (float) $stat->ceiling_amount;
        $rate           = (float) $stat->rate / 100;
        $ceilingApplied = false;

        if ($gross > $ceilingSalary) {
            $amount         = $ceilingAmount;
            $ceilingApplied = true;
        } else {
            $amount = round($gross * $rate, 2);
        }

        return [$amount, $stat->rate, $ceilingApplied];
    }

    /**
     * PCB / MTD Calculation
     * Steps:
     * 1. Annualise monthly gross (× 12)
     * 2. Subtract standard relief (self: 9000)
     * 3. Subtract spouse relief if married_spouse_not_working (4000)
     * 4. Subtract child relief (2000 per child, max configurable)
     * 5. Lookup bracket → compute annual tax
     * 6. Divide by 12 → monthly PCB
     */
    private function calcPcb(
        float $gross,
        int $year,
        string $maritalStatus,
        int $childrenCount
    ): array {
        $annualGross = $gross * 12;

        // Standard reliefs (LHDN 2026)
        $reliefSelf    = 9000;
        $reliefSpouse  = ($maritalStatus === 'married_spouse_not_working') ? 4000 : 0;
        $reliefChild   = $childrenCount * 2000;  // RM2,000 per child (simplified)

        $annualTaxable = max(0, $annualGross - $reliefSelf - $reliefSpouse - $reliefChild);

        // Lookup PCB bracket
        $bracket = DB::table('pcb_brackets')
            ->where('year', $year)
            ->where('marital_status', $maritalStatus)
            ->where('children_count', 0) // simplified: use 0-children table, adjust by relief
            ->where('income_from', '<=', $annualTaxable)
            ->where(function ($q) use ($annualTaxable) {
                $q->where('income_to', '>=', $annualTaxable)
                  ->orWhereNull('income_to');
            })
            ->orderByDesc('income_from')
            ->first();

        if (!$bracket) return [0, $annualTaxable];

        $excess    = max(0, $annualTaxable - $bracket->income_from);
        $annualTax = $bracket->base_tax + ($excess * $bracket->marginal_rate / 100);
        $monthlyPcb = round($annualTax / 12, 2);

        return [$monthlyPcb, $annualTaxable];
    }

    // ── Private: Helpers ──────────────────────────────────────────────────

    private function recalculateRunTotals(PayrollRun $run): void
    {
        $run->load('lines.deductions');

        $totalGross   = 0;
        $totalEeDeduct = 0;
        $totalErCost  = 0;
        $totalNet     = 0;
        $totalKwsp    = 0;
        $totalSocso   = 0;
        $totalEis     = 0;
        $totalPcb     = 0;

        foreach ($run->lines as $line) {
            $totalGross    += (float) $line->gross_salary;
            $totalEeDeduct += (float) $line->total_employee_deduction;
            $totalErCost   += (float) $line->total_employer_cost;
            $totalNet      += (float) $line->net_salary;

            foreach ($line->deductions as $d) {
                match ($d->component) {
                    'KWSP_EE', 'KWSP_ER' => $totalKwsp  += (float) $d->amount,
                    'SOCSO_EE','SOCSO_ER' => $totalSocso += (float) $d->amount,
                    'EIS_EE',  'EIS_ER'  => $totalEis   += (float) $d->amount,
                    'PCB'                => $totalPcb   += (float) $d->amount,
                    default              => null,
                };
            }
        }

        $run->update([
            'total_gross'              => round($totalGross, 2),
            'total_employee_deduction' => round($totalEeDeduct, 2),
            'total_employer_cost'      => round($totalErCost, 2),
            'total_net_salary'         => round($totalNet, 2),
            'total_kwsp'               => round($totalKwsp, 2),
            'total_socso'              => round($totalSocso, 2),
            'total_eis'                => round($totalEis, 2),
            'total_pcb'                => round($totalPcb, 2),
        ]);
    }

    private function sumDeductionComponent(PayrollRun $run, string $component): float
    {
        return (float) DB::table('payroll_line_deductions')
            ->join('payroll_lines', 'payroll_lines.id', '=', 'payroll_line_deductions.payroll_line_id')
            ->where('payroll_lines.payroll_run_id', $run->id)
            ->where('payroll_line_deductions.component', $component)
            ->sum('payroll_line_deductions.amount');
    }

    private function loadGlMappings(int $companyId): array
    {
        $components = [
            'SALARY_EXPENSE',
            'EMPLOYER_CONTRIBUTION_EXPENSE',
            'KWSP_PAYABLE',
            'SOCSO_PAYABLE',
            'EIS_PAYABLE',
            'PCB_PAYABLE',
            'NET_SALARY_PAYABLE',
        ];

        $mappings = [];
        foreach ($components as $component) {
            $accountId = PayrollGlMapping::accountFor($companyId, $component);
            if (!$accountId) {
                throw new \Exception("GL mapping missing for component: {$component}. Please configure Payroll GL Mappings.");
            }
            $mappings[$component] = $accountId;
        }

        return $mappings;
    }
}
