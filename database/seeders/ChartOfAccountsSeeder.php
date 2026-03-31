<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Standard Malaysian SME Chart of Accounts.
     * Hierarchy: Category (L1) → Group (L2) → Account (L3)
     * Code range: 1000–5999
     *
     * Format: ['code', 'name', 'type', 'level', 'parent_code']
     */
    private array $coa = [
        // ── ASSETS (1000) ────────────────────────────────────────────────────
        ['1000', 'Assets',                            'asset', 1, null],

        ['1100', 'Current Assets',                    'asset', 2, '1000'],
        ['1110', 'Cash at Bank',                      'asset', 3, '1100'],
        ['1120', 'Petty Cash',                        'asset', 3, '1100'],
        ['1130', 'Trade Receivables',                 'asset', 3, '1100'],
        ['1140', 'Other Receivables',                 'asset', 3, '1100'],
        ['1150', 'Inventory',                         'asset', 3, '1100'],
        ['1160', 'Prepaid Expenses',                  'asset', 3, '1100'],
        ['1170', 'SST Receivable',                    'asset', 3, '1100'],

        ['1200', 'Non-Current Assets',                'asset', 2, '1000'],
        ['1210', 'Plant & Equipment (at cost)',       'asset', 3, '1200'],
        ['1220', 'Motor Vehicles (at cost)',          'asset', 3, '1200'],
        ['1230', 'Office Equipment (at cost)',        'asset', 3, '1200'],
        ['1240', 'Furniture & Fittings (at cost)',   'asset', 3, '1200'],
        ['1250', 'Accum. Depreciation – Plant',      'asset', 3, '1200'],
        ['1260', 'Accum. Depreciation – Vehicles',   'asset', 3, '1200'],
        ['1270', 'Accum. Depreciation – Office Equip', 'asset', 3, '1200'],
        ['1280', 'Accum. Depreciation – Furniture',  'asset', 3, '1200'],

        ['1300', 'Other Assets',                     'asset', 2, '1000'],
        ['1310', 'Security Deposits',                'asset', 3, '1300'],
        ['1320', 'Other Long-term Assets',           'asset', 3, '1300'],

        // ── LIABILITIES (2000) ────────────────────────────────────────────────
        ['2000', 'Liabilities',                      'liability', 1, null],

        ['2100', 'Current Liabilities',              'liability', 2, '2000'],
        ['2110', 'Trade Payables',                   'liability', 3, '2100'],
        ['2120', 'Other Payables & Accruals',        'liability', 3, '2100'],
        ['2130', 'SST Payable',                      'liability', 3, '2100'],
        ['2140', 'EPF Payable',                      'liability', 3, '2100'],
        ['2150', 'SOCSO Payable',                    'liability', 3, '2100'],
        ['2160', 'PCB (Income Tax) Payable',         'liability', 3, '2100'],
        ['2170', 'EIS Payable',                      'liability', 3, '2100'],
        ['2180', 'Short-term Borrowings',            'liability', 3, '2100'],
        ['2190', 'Deferred Revenue',                 'liability', 3, '2100'],

        ['2200', 'Non-Current Liabilities',          'liability', 2, '2000'],
        ['2210', 'Long-term Borrowings',             'liability', 3, '2200'],
        ['2220', 'Hire Purchase Payable',            'liability', 3, '2200'],
        ['2230', 'Finance Lease Payable',            'liability', 3, '2200'],

        // ── EQUITY (3000) ─────────────────────────────────────────────────────
        ['3000', 'Equity',                           'equity', 1, null],

        ['3100', 'Share Capital & Reserves',         'equity', 2, '3000'],
        ['3110', 'Paid-up Capital',                  'equity', 3, '3100'],
        ['3120', 'Retained Earnings',                'equity', 3, '3100'],
        ['3130', 'Current Year Profit / (Loss)',     'equity', 3, '3100'],
        ['3140', 'Drawings',                         'equity', 3, '3100'],

        // ── REVENUE (4000) ────────────────────────────────────────────────────
        ['4000', 'Revenue',                          'revenue', 1, null],

        ['4100', 'Operating Revenue',                'revenue', 2, '4000'],
        ['4110', 'Sales',                            'revenue', 3, '4100'],
        ['4120', 'Service Revenue',                  'revenue', 3, '4100'],
        ['4130', 'Sales Returns & Discounts',        'revenue', 3, '4100'],

        ['4200', 'Other Income',                     'revenue', 2, '4000'],
        ['4210', 'Interest Income',                  'revenue', 3, '4200'],
        ['4220', 'Rental Income',                    'revenue', 3, '4200'],
        ['4230', 'Discount Received',                'revenue', 3, '4200'],
        ['4240', 'Gain on Disposal of Assets',      'revenue', 3, '4200'],
        ['4250', 'Miscellaneous Income',             'revenue', 3, '4200'],

        // ── EXPENSES (5000) ────────────────────────────────────────────────────
        ['5000', 'Expenses',                         'expense', 1, null],

        ['5100', 'Cost of Sales',                    'expense', 2, '5000'],
        ['5110', 'Cost of Goods Sold',               'expense', 3, '5100'],
        ['5120', 'Direct Labour',                    'expense', 3, '5100'],
        ['5130', 'Manufacturing Overhead',           'expense', 3, '5100'],

        ['5200', 'Staff Costs',                      'expense', 2, '5000'],
        ['5210', 'Salaries & Wages',                 'expense', 3, '5200'],
        ['5220', 'EPF – Employer Contribution',      'expense', 3, '5200'],
        ['5230', 'SOCSO – Employer Contribution',    'expense', 3, '5200'],
        ['5240', 'EIS – Employer Contribution',      'expense', 3, '5200'],
        ['5250', 'Staff Benefits & Allowances',      'expense', 3, '5200'],
        ['5260', 'Staff Training',                   'expense', 3, '5200'],

        ['5300', 'Operating Expenses',               'expense', 2, '5000'],
        ['5310', 'Rental Expense',                   'expense', 3, '5300'],
        ['5320', 'Utilities',                        'expense', 3, '5300'],
        ['5330', 'Office Supplies & Stationery',     'expense', 3, '5300'],
        ['5340', 'Telephone & Internet',             'expense', 3, '5300'],
        ['5350', 'Advertising & Promotion',          'expense', 3, '5300'],
        ['5360', 'Repairs & Maintenance',            'expense', 3, '5300'],
        ['5370', 'Insurance',                        'expense', 3, '5300'],
        ['5380', 'Postage & Courier',                'expense', 3, '5300'],

        ['5400', 'Finance Costs',                    'expense', 2, '5000'],
        ['5410', 'Bank Charges',                     'expense', 3, '5400'],
        ['5420', 'Interest Expense',                 'expense', 3, '5400'],
        ['5430', 'Hire Purchase Interest',           'expense', 3, '5400'],

        ['5500', 'Depreciation & Amortisation',      'expense', 2, '5000'],
        ['5510', 'Depreciation – Plant & Equipment', 'expense', 3, '5500'],
        ['5520', 'Depreciation – Motor Vehicles',    'expense', 3, '5500'],
        ['5530', 'Depreciation – Office Equipment',  'expense', 3, '5500'],
        ['5540', 'Depreciation – Furniture',         'expense', 3, '5500'],

        ['5600', 'Other Expenses',                   'expense', 2, '5000'],
        ['5610', 'Entertainment',                    'expense', 3, '5600'],
        ['5620', 'Travel & Transportation',          'expense', 3, '5600'],
        ['5630', 'Professional Fees',                'expense', 3, '5600'],
        ['5640', 'Subscriptions & Memberships',      'expense', 3, '5600'],
        ['5650', 'Donations & Gifts',                'expense', 3, '5600'],
        ['5660', 'Bad Debts Written Off',            'expense', 3, '5600'],
        ['5670', 'Loss on Disposal of Assets',       'expense', 3, '5600'],
        ['5680', 'Miscellaneous Expenses',           'expense', 3, '5600'],
    ];

    public function run(): void
    {
        // Ensure a company exists; create a demo one if not
        $company = Company::first() ?? Company::create([
            'name'                 => 'Demo Company Sdn. Bhd.',
            'registration_number'  => '202401000001',
            'country'              => 'MY',
            'currency'             => 'MYR',
            'timezone'             => 'Asia/Kuala_Lumpur',
            'financial_year_start' => '2024-01-01',
            'is_active'            => true,
        ]);

        // Attach admin user to company if not already assigned
        User::where('email', 'admin@saga-sme.test')
            ->whereNull('company_id')
            ->update(['company_id' => $company->id]);

        // Skip if COA already seeded for this company
        if (Account::withoutGlobalScopes()->where('company_id', $company->id)->exists()) {
            $this->command->info("COA already seeded for company [{$company->name}] — skipping.");
            return;
        }

        $this->command->info("Seeding Chart of Accounts for [{$company->name}]...");

        // Track code → id for parent lookups
        $codeToId = [];

        foreach ($this->coa as [$code, $name, $type, $level, $parentCode]) {
            $account = Account::withoutGlobalScopes()->create([
                'company_id'  => $company->id,
                'parent_id'   => $parentCode ? ($codeToId[$parentCode] ?? null) : null,
                'code'        => $code,
                'name'        => $name,
                'type'        => $type,
                'level'       => $level,
                'is_active'   => true,
            ]);

            $codeToId[$code] = $account->id;
        }

        $count = count($this->coa);
        $this->command->info("Created {$count} accounts.");
    }
}
