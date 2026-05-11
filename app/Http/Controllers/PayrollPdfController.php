<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollLine;
use App\Models\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PayrollPdfController extends Controller
{
    // ── Payslip — single employee ─────────────────────────────────
    public function payslip(PayrollRun $run, PayrollLine $line): Response
    {
        $company = Company::find($run->company_id);
        $line->load('employee');

        $deductions   = $line->deductions()->whereIn('component', ['kwsp_ee', 'socso_ee', 'eis_ee', 'pcb'])->get();
        $erDeductions = $line->deductions()->whereIn('component', ['kwsp_er', 'socso_er', 'eis_er'])->get();

        $pdf = Pdf::loadView('reports.payslip-pdf', compact(
            'company', 'run', 'line', 'deductions', 'erDeductions'
        ))->setPaper('a4', 'portrait');

        $filename = 'Payslip-' . $line->employee->employee_no . '-' . ($run->payrollPeriod->name ?? $run->id) . '.pdf';
        $filename = str_replace(' ', '-', $filename);

        return $pdf->download($filename);
    }

    // ── Payslip — all employees in a run ─────────────────────────
    public function payslipAll(PayrollRun $run): Response
    {
        $company = Company::find($run->company_id);
        $lines   = $run->lines()->with(['employee', 'deductions'])->get();

        $pdf = Pdf::loadView('reports.payslip-all-pdf', compact(
            'company', 'run', 'lines'
        ))->setPaper('a4', 'portrait');

        $filename = 'Payslip-All-' . ($run->payrollPeriod->name ?? $run->id) . '.pdf';
        $filename = str_replace(' ', '-', $filename);

        return $pdf->download($filename);
    }

    // ── EA Form — per employee per year ──────────────────────────
    public function eaForm(Employee $employee, int $year): Response
    {
        $company = Company::find($employee->company_id);

        // Aggregate all payroll lines for this employee in this year
        $lines = PayrollLine::whereHas('payrollRun', function ($q) use ($employee, $year) {
                $q->where('company_id', $employee->company_id)
                  ->where('status', 'posted')
                  ->whereHas('payrollPeriod', fn ($q2) => $q2->where('year', $year));
            })
            ->where('employee_id', $employee->id)
            ->with('deductions')
            ->get();

        // Aggregate summary
        $summary = [
            'basic_salary'       => $lines->sum('basic_salary'),
            'allowances'         => $lines->sum('allowances'),
            'gross_salary'       => $lines->sum('gross_salary'),
            'total_ee_deduction' => $lines->sum('total_employee_deduction'),
            'kwsp_ee'            => $lines->flatMap->deductions->where('component', 'kwsp_ee')->sum('amount'),
            'socso_ee'           => $lines->flatMap->deductions->where('component', 'socso_ee')->sum('amount'),
            'eis_ee'             => $lines->flatMap->deductions->where('component', 'eis_ee')->sum('amount'),
            'pcb'                => $lines->flatMap->deductions->where('component', 'pcb')->sum('amount'),
        ];

        $pdf = Pdf::loadView('reports.ea-form-pdf', compact(
            'company', 'employee', 'year', 'summary'
        ))->setPaper('a4', 'portrait');

        $filename = 'EA-Form-' . $employee->employee_no . '-' . $year . '.pdf';

        return $pdf->download($filename);
    }
}
