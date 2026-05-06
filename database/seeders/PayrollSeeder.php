<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedStatutoryRates();
        $this->seedPcbBrackets();
        $this->seedGlMappings();
    }

    // ── 1. Statutory Rate Versions 2026 ───────────────────────────────────
    private function seedStatutoryRates(): void
    {
        $rates = [
            // KWSP / EPF
            // Employee: 11% (standard), no ceiling on salary but contribution capped
            [
                'year'            => 2026,
                'type'            => 'KWSP_EE',
                'rate'            => 11.0000,
                'ceiling_salary'  => null,      // no salary ceiling for employee
                'ceiling_amount'  => null,
                'effective_from'  => '2026-01-01',
            ],
            // Employer: 13% if salary <= 5000, 12% if > 5000
            // We store two rows — PayrollService picks based on salary
            [
                'year'            => 2026,
                'type'            => 'KWSP_ER',
                'rate'            => 13.0000,   // default (<=5000)
                'ceiling_salary'  => 5000.00,   // applies when salary <= this
                'ceiling_amount'  => null,
                'effective_from'  => '2026-01-01',
            ],

            // SOCSO — Jadual 1 (both employee + employer)
            // Ceiling: salary RM 5,000/month → contribution capped
            // Employee: 0.5%, Employer: 1.75%
            [
                'year'            => 2026,
                'type'            => 'SOCSO_EE',
                'rate'            => 0.5000,
                'ceiling_salary'  => 5000.00,
                'ceiling_amount'  => 24.75,     // max employee SOCSO (0.5% of 4950 = 24.75 per SOCSO table)
                'effective_from'  => '2026-01-01',
            ],
            [
                'year'            => 2026,
                'type'            => 'SOCSO_ER',
                'rate'            => 1.7500,
                'ceiling_salary'  => 5000.00,
                'ceiling_amount'  => 86.65,     // max employer SOCSO
                'effective_from'  => '2026-01-01',
            ],

            // EIS — Employment Insurance System
            // Employee: 0.2%, Employer: 0.2%
            // Ceiling salary: RM 5,000/month
            [
                'year'            => 2026,
                'type'            => 'EIS_EE',
                'rate'            => 0.2000,
                'ceiling_salary'  => 5000.00,
                'ceiling_amount'  => 9.90,      // max EIS employee
                'effective_from'  => '2026-01-01',
            ],
            [
                'year'            => 2026,
                'type'            => 'EIS_ER',
                'rate'            => 0.2000,
                'ceiling_salary'  => 5000.00,
                'ceiling_amount'  => 9.90,      // max EIS employer
                'effective_from'  => '2026-01-01',
            ],
        ];

        foreach ($rates as $rate) {
            DB::table('statutory_rate_versions')->updateOrInsert(
                ['year' => $rate['year'], 'type' => $rate['type']],
                array_merge($rate, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Statutory rates 2026 seeded.');
    }

    // ── 2. PCB Brackets 2026 (LHDN MTD) ──────────────────────────────────
    // Source: LHDN MTD schedule 2026
    // Simplified: Single + Married (spouse working) + Married (spouse not working)
    // Children count: 0 (add more rows for 1,2,3 children as needed)
    private function seedPcbBrackets(): void
    {
        // Annual taxable income brackets
        // Formula: tax = base_tax + (income - income_from) * marginal_rate / 100
        $brackets = [
            // ── SINGLE (0 children) ──────────────────────────────────────
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 0,       'income_to' => 5000,    'base_tax' => 0,       'marginal_rate' => 0],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 5001,    'income_to' => 20000,   'base_tax' => 0,       'marginal_rate' => 1],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 20001,   'income_to' => 35000,   'base_tax' => 150,     'marginal_rate' => 3],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 35001,   'income_to' => 50000,   'base_tax' => 600,     'marginal_rate' => 8],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 50001,   'income_to' => 70000,   'base_tax' => 1800,    'marginal_rate' => 13],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 70001,   'income_to' => 100000,  'base_tax' => 4400,    'marginal_rate' => 21],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 100001,  'income_to' => 250000,  'base_tax' => 10700,   'marginal_rate' => 24],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 250001,  'income_to' => 400000,  'base_tax' => 46700,   'marginal_rate' => 24.5],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 400001,  'income_to' => 600000,  'base_tax' => 83450,   'marginal_rate' => 25],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 600001,  'income_to' => 1000000, 'base_tax' => 133450,  'marginal_rate' => 26],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 1000001, 'income_to' => 2000000, 'base_tax' => 237450,  'marginal_rate' => 28],
            ['marital_status' => 'single', 'children_count' => 0, 'income_from' => 2000001, 'income_to' => null,    'base_tax' => 517450,  'marginal_rate' => 30],

            // ── MARRIED, SPOUSE WORKING (0 children) ─────────────────────
            // Same brackets as single — relief applied at calculation level
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 0,       'income_to' => 5000,    'base_tax' => 0,       'marginal_rate' => 0],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 5001,    'income_to' => 20000,   'base_tax' => 0,       'marginal_rate' => 1],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 20001,   'income_to' => 35000,   'base_tax' => 150,     'marginal_rate' => 3],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 35001,   'income_to' => 50000,   'base_tax' => 600,     'marginal_rate' => 8],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 50001,   'income_to' => 70000,   'base_tax' => 1800,    'marginal_rate' => 13],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 70001,   'income_to' => 100000,  'base_tax' => 4400,    'marginal_rate' => 21],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 100001,  'income_to' => 250000,  'base_tax' => 10700,   'marginal_rate' => 24],
            ['marital_status' => 'married_spouse_working', 'children_count' => 0, 'income_from' => 250001,  'income_to' => null,    'base_tax' => 46700,   'marginal_rate' => 24.5],

            // ── MARRIED, SPOUSE NOT WORKING (0 children) ─────────────────
            // Higher relief — lower effective tax
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 0,       'income_to' => 5000,    'base_tax' => 0,      'marginal_rate' => 0],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 5001,    'income_to' => 20000,   'base_tax' => 0,      'marginal_rate' => 1],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 20001,   'income_to' => 35000,   'base_tax' => 150,    'marginal_rate' => 3],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 35001,   'income_to' => 50000,   'base_tax' => 600,    'marginal_rate' => 8],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 50001,   'income_to' => 70000,   'base_tax' => 1800,   'marginal_rate' => 13],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 70001,   'income_to' => 100000,  'base_tax' => 4400,   'marginal_rate' => 21],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 100001,  'income_to' => 250000,  'base_tax' => 10700,  'marginal_rate' => 24],
            ['marital_status' => 'married_spouse_not_working', 'children_count' => 0, 'income_from' => 250001,  'income_to' => null,    'base_tax' => 46700,  'marginal_rate' => 24.5],
        ];

        foreach ($brackets as $bracket) {
            DB::table('pcb_brackets')->updateOrInsert(
                [
                    'year'           => 2026,
                    'marital_status' => $bracket['marital_status'],
                    'children_count' => $bracket['children_count'],
                    'income_from'    => $bracket['income_from'],
                ],
                array_merge(['year' => 2026], $bracket, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ PCB brackets 2026 seeded (' . count($brackets) . ' rows).');
    }

    // ── 3. Payroll GL Mappings — company_id = 1 ──────────────────────────
    // Maps payroll components to COA accounts
    // Based on confirmed COA: 5210, 5220, 5230, 5240, 2140, 2150, 2160, 2170, 2120
    private function seedGlMappings(): void
    {
        // Get account IDs by code for company_id = 1
        $accounts = DB::table('accounts')
            ->where('company_id', 1)
            ->whereIn('code', ['5210', '5220', '5230', '5240', '2140', '2150', '2160', '2170', '2120'])
            ->pluck('id', 'code');

        if ($accounts->isEmpty()) {
            $this->command->warn('⚠️  No accounts found for company_id=1. GL mappings skipped.');
            return;
        }

        $mappings = [
            'SALARY_EXPENSE'               => '5210',  // Salaries & Wages
            'EMPLOYER_CONTRIBUTION_EXPENSE' => '5220',  // EPF Employer (grouped — SOCSO/EIS also post here for simplicity)
            'KWSP_PAYABLE'                 => '2140',  // EPF Payable
            'SOCSO_PAYABLE'                => '2150',  // SOCSO Payable
            'EIS_PAYABLE'                  => '2170',  // EIS Payable
            'PCB_PAYABLE'                  => '2160',  // PCB (Income Tax) Payable
            'NET_SALARY_PAYABLE'           => '2120',  // Other Payables & Accruals
        ];

        foreach ($mappings as $component => $code) {
            if (!isset($accounts[$code])) {
                $this->command->warn("⚠️  Account {$code} not found — skipping {$component}");
                continue;
            }

            DB::table('payroll_gl_mappings')->updateOrInsert(
                ['company_id' => 1, 'component' => $component],
                [
                    'account_id' => $accounts[$code],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ Payroll GL mappings seeded for company_id=1.');
    }
}
