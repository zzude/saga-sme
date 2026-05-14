<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * DemoDataSeeder — SAGA SME realistic Malaysian demo data
 *
 * Pre-requisites (run before this):
 *   RoleSeeder, AdminUserSeeder, ChartOfAccountsSeeder,
 *   CurrencySeeder, BaseCurrencyPatchSeeder
 *
 * Run: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private int   $companyId;
    private array $acct       = [];  // code  => account_id
    private array $customers  = [];  // index => customer_id
    private array $vendors    = [];  // index => vendor_id
    private array $employees  = [];  // index => employee_id
    private array $periods    = [];  // 'YYYY-MM' => accounting_period_id
    private array $pPeriods   = [];  // 'YYYY-MM' => payroll_period_id
    private array $leaveTypes = [];  // slug => leave_type_id

    private const SST      = 8.00;
    private const FX_MAR25 = 4.4650;
    private const FX_JUN25 = 4.4680;
    private const FX_NOV25 = 4.4720;
    private const FX_FEB26 = 4.4800;

    // ════════════════════════════════════════════════════════════════
    public function run(): void
    {
        // Truncate demo tables (order matters — children first)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('billplz_bills')->truncate();
        DB::table('leave_applications')->truncate();
        DB::table('leave_balances')->truncate();
        DB::table('leave_types')->truncate();
        DB::table('cash_advance_settlements')->truncate();
        DB::table('cash_advances')->truncate();
        DB::table('staff_loan_repayments')->truncate();
        DB::table('staff_loans')->truncate();
        DB::table('payroll_line_deductions')->truncate();
        DB::table('payroll_lines')->truncate();
        DB::table('payroll_runs')->truncate();
        DB::table('payroll_periods')->truncate();
        DB::table('employees')->truncate();
        DB::table('bill_lines')->truncate();
        DB::table('bills')->truncate();
        DB::table('invoice_lines')->truncate();
        DB::table('invoices')->truncate();
        DB::table('vendors')->truncate();
        DB::table('customers')->truncate();
        DB::table('accounting_periods')->truncate();
        DB::table('journal_lines')->truncate();
        DB::table('journal_headers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('   ↳ Truncate selesai.');

        $this->loadAccounts();
        $this->resolveCompany();
        $this->seedAccountingPeriods();
        $this->seedCustomers();
        $this->seedVendors();
        $this->seedInvoices();
        $this->seedBills();
        $this->seedEmployees();
        $this->seedLeaveTypes();
        $this->seedPayrollPeriodsAndRuns();
        $this->seedStaffLoans();
        $this->seedCashAdvances();
        $this->seedLeaveApplications();
        $this->seedBillplzBills();
        $this->seedJournals();
        $this->seedPayrollLineDeductions();

        $this->command->info('✅  DemoDataSeeder selesai.');
    }

    // ────────────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────────────
    private function loadAccounts(): void
    {
        foreach (DB::table('accounts')->get(['id', 'code']) as $row) {
            $this->acct[$row->code] = $row->id;
        }
        if (empty($this->acct)) {
            $this->command->error('❌  accounts table kosong. Run ChartOfAccountsSeeder dulu.');
            exit(1);
        }
    }

    private function acct(string $code): ?int
    {
        if (!isset($this->acct[$code])) {
            $this->command->warn("⚠️  COA code [{$code}] tidak dijumpai.");
            return null;
        }
        return $this->acct[$code];
    }

    private function resolveCompany(): void
    {
        $existing = DB::table('companies')->first();
        if ($existing) {
            $this->companyId = $existing->id;
            $this->command->info("   ↳ Company: [{$existing->name}] (id={$this->companyId})");
            return;
        }
        $this->companyId = DB::table('companies')->insertGetId([
            'name'          => 'Technika Maju Sdn. Bhd.',
            'registration_no' => '202301012345',
            'base_currency' => 'MYR',
            'created_at'    => now(), 'updated_at' => now(),
        ]);
    }

    private function period(string $date): ?int
    {
        return $this->periods[substr($date, 0, 7)] ?? null;
    }

    // ════════════════════════════════════════════════════════════════
    // 1. ACCOUNTING PERIODS
    // ════════════════════════════════════════════════════════════════
    private function seedAccountingPeriods(): void
    {
        $ranges = [
            ...array_map(fn($m) => [2025, $m, $m <= 11 ? 'closed' : 'open'], range(1, 12)),
            ...array_map(fn($m) => [2026, $m, 'open'], range(1, 6)),
        ];

        foreach ($ranges as [$year, $month, $status]) {
            $start = Carbon::create($year, $month, 1);
            $key   = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            $existing = DB::table('accounting_periods')
                ->where('company_id', $this->companyId)
                ->where('start_date', $start->toDateString())
                ->value('id');

            $this->periods[$key] = $existing ?: DB::table('accounting_periods')->insertGetId([
                'company_id' => $this->companyId,
                'name'       => $start->format('F Y'),
                'start_date' => $start->toDateString(),
                'end_date'   => $start->copy()->endOfMonth()->toDateString(),
                'status'     => $status,
                'closed_by'  => null,
                'closed_at'  => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 2. CUSTOMERS
    // ════════════════════════════════════════════════════════════════
    private function seedCustomers(): void
    {
        $list = [
            ['CUST-001','PETRONAS Dagangan Berhad',
                'procurement@petronas.com.my','+603-2051 5000',
                'Tower 1, PETRONAS Twin Towers, KLCC','Kuala Lumpur','Wilayah Persekutuan','50088','Malaysia',
                false,'196801000109','C196801000109','C196801000109','BRN','196801000109',50000,30],
            ['CUST-002','Telekom Malaysia Berhad',
                'vendor@tm.com.my','+603-2240 1221',
                'Level 51, Menara TM, Jalan Pantai Baharu','Kuala Lumpur','Wilayah Persekutuan','50672','Malaysia',
                false,'198201010209','C198201010209','C198201010209','BRN','198201010209',80000,30],
            ['CUST-003','MIMOS Berhad',
                'it.vendor@mimos.my','+603-8995 5000',
                'Technology Park Malaysia, Bukit Jalil','Kuala Lumpur','Wilayah Persekutuan','57000','Malaysia',
                false,'199301015302','C199301015302','C199301015302','BRN','199301015302',60000,30],
            ['CUST-004','Sarawak Information Systems Sdn Bhd',
                'finance@sains.com.my','+6082-440 888',
                'Jalan Bako','Kuching','Sarawak','93050','Malaysia',
                false,'200301018756','C200301018756','C200301018756','BRN','200301018756',70000,45],
            ['CUST-005','Ecoworld Development Group Berhad',
                'it.procurement@ecoworld.com.my','+603-7450 8118',
                'Level 32, Mercu 2, KL Eco City','Kuala Lumpur','Wilayah Persekutuan','59200','Malaysia',
                false,'201301041230','C201301041230','C201301041230','BRN','201301041230',40000,30],
            ['CUST-006','Singapore Tech Solutions Pte Ltd',
                'ap@sgtech.sg','+65-6222 3333',
                '18 Cross Street, #10-08 China Square','Singapore','-','048423','Singapore',
                false,'SG201234567A',null,null,'BRN','SG201234567A',60000,30],
            ['CUST-007','Brunei ICT Services Sdn Bhd',
                'accounts@bitsb.com.bn','+673-2-222 333',
                'Unit 5, Bangunan Guru-guru','Bandar Seri Begawan','-','BA1310','Brunei',
                false,null,null,null,null,null,30000,45],
            ['CUST-008','Mohd Hafiz bin Ibrahim',
                'hafiz.ibrahim@gmail.com','+6012-345 6789',
                'No. 12, Jalan Mawar 3, Taman Bukit Indah','Ampang','Selangor','68000','Malaysia',
                true,null,null,null,'NRIC','820415-12-5001',5000,14],
            ['CUST-009','Nurul Ain binti Roslan',
                'nurulain.roslan@gmail.com','+6019-876 5432',
                'Blok C-15-3, Residensi Harmoni','Petaling Jaya','Selangor','47810','Malaysia',
                true,null,null,null,'NRIC','940322-10-5002',5000,14],
            ['CUST-010','David Lim Chee Wah',
                'david.lim@outlook.com','+6016-234 5678',
                '28, Jalan Puah, Taman Desa','Kuala Lumpur','Wilayah Persekutuan','58100','Malaysia',
                true,null,null,null,'NRIC','880507-14-5003',5000,14],
        ];

        foreach ($list as [
            $code,$name,$email,$phone,$address,$city,$state,$postcode,$country,
            $isIndividual,$regNo,$taxId,$tin,$idType,$idValue,$creditLimit,$creditTermDays
        ]) {
            $this->customers[] = DB::table('customers')->insertGetId([
                'company_id'          => $this->companyId,
                'customer_code'       => $code,
                'name'                => $name,
                'email'               => $email,
                'phone'               => $phone,
                'address'             => $address,
                'city'                => $city,
                'state'               => $state,
                'postcode'            => $postcode,
                'country'             => $country,
                'is_individual'       => $isIndividual,
                'registration_no'     => $regNo,
                'tax_id'              => $taxId,
                'tin'                 => $tin,
                'id_type'             => $idType,
                'id_value'            => $idValue,
                'sst_registration_no' => null,
                'msic_code'           => null,
                'credit_limit'        => $creditLimit,
                'credit_term_days'    => $creditTermDays,
                'is_active'           => true,
                'created_at'          => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 3. VENDORS
    // ════════════════════════════════════════════════════════════════
    private function seedVendors(): void
    {
        $list = [
            ['VEND-001','Dell Technologies (M) Sdn Bhd',   'Account Manager',  'ap@dell.com.my',          '+603-2050 6000','Level 17, Menara BRDB, 285 Jalan Maarof','Kuala Lumpur',    'Wilayah Persekutuan','59000','Malaysia','198901012345','C198901012345','MYR',30,100000],
            ['VEND-002','Microsoft Malaysia Sdn Bhd',       'Sales Team',       'malaysia@microsoft.com',  '+603-2179 5000','Level 21, UOA Centre, 19 Jalan Pinang', 'Kuala Lumpur',    'Wilayah Persekutuan','50450','Malaysia','198901023456','C198901023456','MYR',30, 80000],
            ['VEND-003','Maxis Berhad',                     'Corporate Account','corporate@maxis.com.my',  '+603-2330 7000','Level 18, Menara Maxis, KLCC',           'Kuala Lumpur',    'Wilayah Persekutuan','50088','Malaysia','200101034567','C200101034567','MYR',30, 20000],
            ['VEND-004','Tenaga Nasional Berhad',            'Billing Dept',     'accounts@tnb.com.my',     '+603-2296 5566','No. 129, Jalan Bangsar',                 'Kuala Lumpur',    'Wilayah Persekutuan','59200','Malaysia','199001045678','C199001045678','MYR',14, 10000],
            ['VEND-005','Mudah.my Sdn Bhd',                 'Digital Sales',    'billing@mudah.my',        '+603-2779 1100','Level 9, Axiata Tower, Jalan Stesen Sentral','Kuala Lumpur','Wilayah Persekutuan','50470','Malaysia','200601056789','C200601056789','MYR',30, 10000],
            ['VEND-006','Sunway Property Management Sdn Bhd','Property Manager','finance@sunway.com.my',   '+603-7492 8888','No. 2, Jalan PJS 8/1, Sunway City',    'Subang Jaya',     'Selangor',           '47500','Malaysia','198901067890','C198901067890','MYR',30, 60000],
            ['VEND-007','Amazon Web Services (AWS)',         'Billing Team',     'aws-billing@amazon.com',  '+1-206-266-1000','410 Terry Ave N',                      'Seattle',         'Washington',         '98109','USA',     null,          null,           'USD',30, 50000],
            ['VEND-008','Alibaba Cloud International',       'Cloud Sales',      'billing@alibabacloud.com','+86-571-8502 2088','969 West Wenyi Road',               'Hangzhou',        'Zhejiang',           '311121','China',  null,          null,           'USD',30, 30000],
        ];

        foreach ($list as [
            $code,$name,$contact,$email,$phone,$address,$city,$state,
            $postcode,$country,$regNo,$taxId,$currency,$terms,$limit
        ]) {
            $this->vendors[] = DB::table('vendors')->insertGetId([
                'company_id'      => $this->companyId,
                'vendor_code'     => $code,
                'name'            => $name,
                'contact_person'  => $contact,
                'email'           => $email,
                'phone'           => $phone,
                'address'         => $address,
                'city'            => $city,
                'state'           => $state,
                'postcode'        => $postcode,
                'country'         => $country,
                'registration_no' => $regNo,
                'tax_id'          => $taxId,
                'currency_code'   => $currency,
                'credit_term_days'=> $terms,
                'credit_limit'    => $limit,
                'is_active'       => true,
                'created_at'      => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 4. INVOICES + LINES
    // ════════════════════════════════════════════════════════════════
    private function seedInvoices(): void
    {
        $sstRate = self::SST / 100;

        $data = [
            [0,'2025-01-15','2025-02-15','MYR',1.0,'paid',[
                ['4120','Pembangunan Sistem ERP – Fasa 1',          1,35000.00,true],
                ['4120','Penyelenggaraan Sistem (Januari)',          1, 2000.00,true],
            ]],
            [1,'2025-02-03','2025-03-05','MYR',1.0,'paid',[
                ['4120','Microsoft 365 Enterprise – 50 Lesen',     50,  180.00,true],
                ['4120','Latihan Keselamatan IT (2 hari)',           1, 4500.00,true],
            ]],
            [2,'2025-02-28','2025-03-30','MYR',1.0,'partial',[
                ['4120','Perundingan Transformasi Digital (Feb)',    1,12000.00,true],
                ['4110','Kit IoT Raspberry Pi (Prototaip)',         5,  850.00,false],
            ]],
            [5,'2025-03-10','2025-04-10','USD',self::FX_MAR25,'paid',[
                ['4120','Perkhidmatan Integrasi API – Fasa 1',      1, 8000.00,false],
                ['4120','Lesen Perisian (Tahunan)',                 1, 1500.00,false],
            ]],
            [3,'2025-04-05','2025-05-05','MYR',1.0,'overdue',[
                ['4120','Naik Taraf Portal e-Kerajaan',             1,55000.00,true],
                ['4120','Sokongan Pasca-Implementasi (Q1)',         3, 3500.00,true],
            ]],
            [4,'2025-05-20','2025-06-20','MYR',1.0,'sent',[
                ['4120','Platform IoT Bangunan Pintar',             1,28000.00,true],
                ['4120','Latihan Perisian BIM (3 sesi)',            3, 2200.00,true],
            ]],
            [6,'2025-06-01','2025-07-01','USD',self::FX_JUN25,'paid',[
                ['4120','Perundingan Migrasi Cloud',                1, 5500.00,false],
                ['4120','Persediaan Pipeline DevOps',               1, 2000.00,false],
            ]],
            [7,'2025-07-12','2025-08-12','MYR',1.0,'paid',[
                ['4120','Pembangunan Laman Web Peribadi',           1, 3500.00,false],
                ['4120','Pengehosan & Penyelenggaraan (1 Tahun)',   1,  600.00,false],
            ]],
            [0,'2025-09-01','2025-10-01','MYR',1.0,'sent',[
                ['4120','ERP Fasa 2 – Modul HR & Penggajian',      1,45000.00,true],
                ['4120','Sokongan Sistem (Q3)',                     3, 2000.00,true],
            ]],
            [1,'2025-10-15','2025-11-15','MYR',1.0,'draft',[
                ['4120','Lesen Tahunan Suite Keselamatan Siber',    1,18000.00,true],
                ['4120','Ujian Penembusan (Penetration Testing)',   1, 9500.00,true],
            ]],
            [5,'2025-11-20','2025-12-20','USD',self::FX_NOV25,'sent',[
                ['4120','Integrasi API – Fasa 2',                   1, 6500.00,false],
                ['4120','Retainer Sokongan Suku Tahunan',           3,  800.00,false],
            ]],
            [8,'2026-01-08','2026-02-08','MYR',1.0,'paid',[
                ['4120','Persediaan Laman Web E-Dagang',            1, 4800.00,false],
            ]],
            [2,'2026-02-14','2026-03-14','MYR',1.0,'sent',[
                ['4120','Integrasi Chatbot AI',                     1,22000.00,true],
                ['4120','Latihan Literasi AI (2 sesi)',             2, 3000.00,true],
            ]],
            [9,'2026-03-05','2026-04-05','MYR',1.0,'draft',[
                ['4120','Pembangunan Aplikasi Mudah Alih (iOS)',    1,12000.00,false],
            ]],
            [3,'2026-04-10','2026-05-10','MYR',1.0,'sent',[
                ['4120','Naik Taraf Sistem Pemantauan Projek',      1,38000.00,true],
                ['4120','Sokongan Teknikal Q1 2026',                3, 2500.00,true],
            ]],
        ];

        $seq = 1;
        foreach ($data as [$custIdx,$date,$due,$currency,$fx,$status,$lines]) {
            $year      = substr($date, 0, 4);
            $invoiceNo = 'INV-' . $year . '-' . str_pad($seq++, 4, '0', STR_PAD_LEFT);

            $subtotal = 0; $taxAmt = 0;
            foreach ($lines as [,,$qty,$price,$applySST]) {
                $la = $qty * $price;
                $subtotal += $la;
                if ($applySST) $taxAmt += $la * $sstRate;
            }
            $subtotal   = round($subtotal, 2);
            $taxAmt     = round($taxAmt, 2);
            $total      = $subtotal + $taxAmt;
            $paidAmt    = match ($status) {
                'paid'    => $total,
                'partial' => round($total * 0.5, 2),
                default   => 0.00,
            };
            $balanceDue = round($total - $paidAmt, 2);
            $isPosted   = !in_array($status, ['draft']);

            $invoiceId = DB::table('invoices')->insertGetId([
                'company_id'         => $this->companyId,
                'customer_id'        => $this->customers[$custIdx],
                'period_id'          => $this->period($date),
                'invoice_no'         => $invoiceNo,
                'date'               => $date,
                'due_date'           => $due,
                'status'             => $status,
                'currency_code'      => $currency,
                'exchange_rate'      => $fx,
                'exchange_rate_date' => $date,
                'rate_source'        => $currency === 'MYR' ? 'MANUAL' : 'AUTO',
                'foreign_subtotal'   => $subtotal,
                'foreign_tax'        => $taxAmt,
                'foreign_total'      => $total,
                'base_subtotal'      => round($subtotal * $fx, 2),
                'base_tax'           => round($taxAmt * $fx, 2),
                'base_total'         => round($total * $fx, 2),
                'subtotal'           => $subtotal,
                'tax_amount'         => $taxAmt,
                'total'              => $total,
                'paid_amount'        => $paidAmt,
                'balance_due'        => $balanceDue,
                'notes'              => $status === 'paid' ? 'Dibayar penuh. Terima kasih.' : 'Terma: Net 30',
                'posted_at'          => $isPosted ? now() : null,
                'einvoice_status'    => 'draft',
                'created_at'         => now(), 'updated_at' => now(),
            ]);

            $sort = 1;
            foreach ($lines as [$code,$desc,$qty,$price,$applySST]) {
                $amt       = round($qty * $price, 2);
                $lineTax   = $applySST ? round($amt * $sstRate, 2) : 0.00;
                $lineTotal = $amt + $lineTax;
                DB::table('invoice_lines')->insert([
                    'invoice_id'         => $invoiceId,
                    'sort_order'         => $sort++,
                    'description'        => $desc,
                    'account_id'         => $this->acct($code),
                    'tax_code_id'        => null,
                    'tax_rate'           => $applySST ? self::SST : 0,
                    'quantity'           => $qty,
                    'unit_price'         => $price,
                    'foreign_unit_price' => $price,
                    'base_unit_price'    => round($price * $fx, 4),
                    'amount'             => $amt,
                    'tax_amount'         => $lineTax,
                    'line_total'         => $lineTotal,
                    'foreign_line_total' => $lineTotal,
                    'base_line_total'    => round($lineTotal * $fx, 2),
                    'created_at'         => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 5. BILLS + LINES
    // ════════════════════════════════════════════════════════════════
    private function seedBills(): void
    {
        $data = [
            // ── Sewa Pejabat (Sunway=5) ─────────────────────────────
            [5,'2025-01-01','2025-01-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Januari 2025',     1,4500.00]]],
            [5,'2025-02-01','2025-02-28','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Februari 2025',    1,4500.00]]],
            [5,'2025-03-01','2025-03-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Mac 2025',         1,4500.00]]],
            [5,'2025-04-01','2025-04-30','MYR',1.0,'paid',    [['5310','Sewa Pejabat – April 2025',       1,4500.00]]],
            [5,'2025-05-01','2025-05-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Mei 2025',         1,4500.00]]],
            [5,'2025-06-01','2025-06-30','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Jun 2025',         1,4500.00]]],
            [5,'2025-07-01','2025-07-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Julai 2025',       1,4500.00]]],
            [5,'2025-08-01','2025-08-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Ogos 2025',        1,4500.00]]],
            [5,'2025-09-01','2025-09-30','MYR',1.0,'paid',    [['5310','Sewa Pejabat – September 2025',   1,4500.00]]],
            [5,'2025-10-01','2025-10-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Oktober 2025',     1,4500.00]]],
            [5,'2025-11-01','2025-11-30','MYR',1.0,'paid',    [['5310','Sewa Pejabat – November 2025',    1,4500.00]]],
            [5,'2025-12-01','2025-12-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Disember 2025',    1,4500.00]]],
            [5,'2026-01-01','2026-01-31','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Januari 2026',     1,4800.00]]],
            [5,'2026-02-01','2026-02-28','MYR',1.0,'paid',    [['5310','Sewa Pejabat – Februari 2026',    1,4800.00]]],
            [5,'2026-03-01','2026-03-31','MYR',1.0,'draft',   [['5310','Sewa Pejabat – Mac 2026',         1,4800.00]]],
            // ── Utiliti (TNB=3) ──────────────────────────────────────
            [3,'2025-01-05','2025-02-05','MYR',1.0,'paid',    [['5320','Bil Elektrik – Januari 2025',     1, 438.50]]],
            [3,'2025-04-05','2025-05-05','MYR',1.0,'paid',    [['5320','Bil Elektrik – April 2025',       1, 501.20]]],
            [3,'2025-07-07','2025-08-07','MYR',1.0,'paid',    [['5320','Bil Elektrik – Julai 2025',       1, 489.80]]],
            [3,'2025-09-05','2025-10-05','MYR',1.0,'paid',    [['5320','Bil Elektrik – September 2025',   1, 512.30]]],
            [3,'2025-12-05','2026-01-05','MYR',1.0,'paid',    [['5320','Bil Elektrik – Disember 2025',    1, 528.00]]],
            [3,'2026-02-05','2026-03-05','MYR',1.0,'paid',    [['5320','Bil Elektrik – Februari 2026',    1, 496.40]]],
            // ── Telco (Maxis=2) ──────────────────────────────────────
            [2,'2025-01-10','2025-02-10','MYR',1.0,'paid',    [['5340','Maxis Postpaid Business – 5 Talian',5,150.00]]],
            [2,'2025-04-10','2025-05-10','MYR',1.0,'paid',    [['5340','Maxis Postpaid Business – 5 Talian',5,150.00]]],
            [2,'2025-07-10','2025-08-10','MYR',1.0,'paid',    [['5340','Maxis Postpaid Business – 5 Talian',5,150.00]]],
            [2,'2025-10-10','2025-11-10','MYR',1.0,'paid',    [['5340','Maxis Postpaid Business – 5 Talian',5,150.00]]],
            [2,'2026-01-10','2026-02-10','MYR',1.0,'paid',    [['5340','Maxis Postpaid Business – 5 Talian',5,155.00]]],
            // ── Perkakasan (Dell=0) ──────────────────────────────────
            [0,'2025-02-01','2025-03-01','MYR',1.0,'paid',[
                ['5110','Dell Latitude 5540 Laptop × 3',              3,5800.00],
                ['5110','USB-C Docking Station × 3',                  3, 350.00],
            ]],
            [0,'2025-06-10','2025-07-10','MYR',1.0,'paid',    [['5110','Dell PowerEdge Server T150',      1,12800.00]]],
            [0,'2025-10-20','2025-11-20','MYR',1.0,'submitted',[['5110','Dell Precision 3680 × 2',        2, 9200.00]]],
            // ── Perisian (Microsoft=1) ───────────────────────────────
            [1,'2025-02-15','2025-03-15','MYR',1.0,'paid',    [['5110','Microsoft Azure CSP – Feb 2025',  1, 2340.00]]],
            [1,'2025-07-01','2025-08-01','MYR',1.0,'submitted',[['5110','Microsoft 365 E3 – Pembaharuan', 1,18600.00]]],
            [1,'2026-03-01','2026-04-01','MYR',1.0,'draft',   [['5110','Microsoft Azure CSP Q1 2026',     1, 3100.00]]],
            // ── Cloud USD (AWS=6) ────────────────────────────────────
            [6,'2025-03-01','2025-04-01','USD',self::FX_MAR25,'paid',    [['5110','AWS EC2 + S3 – Mac 2025',          1,1280.00]]],
            [6,'2025-05-01','2025-06-01','USD',self::FX_MAR25,'paid',    [['5110','AWS EC2 + RDS – Mei 2025',         1,1450.00]]],
            [6,'2025-08-01','2025-09-01','USD',self::FX_JUN25,'paid',    [['5110','AWS – Ogos 2025',                  1,1560.00]]],
            [6,'2025-12-01','2026-01-01','USD',self::FX_NOV25,'submitted',[['5110','AWS – Disember 2025',             1,1680.00]]],
            [6,'2026-02-01','2026-03-01','USD',self::FX_FEB26,'paid',    [['5110','AWS – Februari 2026',              1,1750.00]]],
            // ── Cloud USD (Alibaba=7) ────────────────────────────────
            [7,'2025-04-01','2025-05-01','USD',self::FX_MAR25,'paid',    [['5110','Alibaba Cloud – April 2025',       1, 890.00]]],
            [7,'2025-09-01','2025-10-01','USD',self::FX_JUN25,'paid',    [['5110','Alibaba Cloud – September 2025',   1, 940.00]]],
            // ── Pengiklanan (Mudah.my=4) ─────────────────────────────
            [4,'2025-03-05','2025-04-05','MYR',1.0,'paid',    [['5350','Iklan Premium Mudah.my Q1 2025',  1,1800.00]]],
            [4,'2025-11-01','2025-12-01','MYR',1.0,'paid',    [['5350','Kempen Digital Marketing Q4',     1,3500.00]]],
        ];

        $seq = 1;
        foreach ($data as [$vendIdx,$date,$due,$currency,$fx,$status,$lines]) {
            $year     = substr($date, 0, 4);
            $billNo   = 'BILL-' . $year . '-' . str_pad($seq++, 4, '0', STR_PAD_LEFT);
            $subtotal = round(collect($lines)->sum(fn($l) => $l[2] * $l[3]), 2);
            $isPaid   = $status === 'paid';

            $billId = DB::table('bills')->insertGetId([
                'company_id'         => $this->companyId,
                'vendor_id'          => $this->vendors[$vendIdx],
                'period_id'          => $this->period($date),
                'bill_no'            => $billNo,
                'reference_no'       => 'REF-' . strtoupper(Str::random(6)),
                'date'               => $date,
                'due_date'           => $due,
                'status'             => $status,
                'currency_code'      => $currency,
                'exchange_rate'      => $fx,
                'exchange_rate_date' => $date,
                'rate_source'        => $currency === 'MYR' ? 'MANUAL' : 'AUTO',
                'foreign_subtotal'   => $subtotal,
                'foreign_tax'        => 0,
                'foreign_total'      => $subtotal,
                'base_subtotal'      => round($subtotal * $fx, 2),
                'base_tax'           => 0,
                'base_total'         => round($subtotal * $fx, 2),
                'subtotal'           => $subtotal,
                'tax_amount'         => 0,
                'total'              => $subtotal,
                'paid_amount'        => $isPaid ? $subtotal : 0,
                'balance_due'        => $isPaid ? 0 : $subtotal,
                'notes'              => null,
                'posted_at'          => $isPaid ? now() : null,
                'created_at'         => now(), 'updated_at' => now(),
            ]);

            $sort = 1;
            foreach ($lines as [$code,$desc,$qty,$price]) {
                $amt = round($qty * $price, 2);
                DB::table('bill_lines')->insert([
                    'bill_id'            => $billId,
                    'sort_order'         => $sort++,
                    'description'        => $desc,
                    'account_id'         => $this->acct($code),
                    'quantity'           => $qty,
                    'unit_price'         => $price,
                    'foreign_unit_price' => $price,
                    'base_unit_price'    => round($price * $fx, 4),
                    'amount'             => $amt,
                    'tax_amount'         => 0,
                    'line_total'         => $amt,
                    'foreign_line_total' => $amt,
                    'base_line_total'    => round($amt * $fx, 2),
                    'created_at'         => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 6. EMPLOYEES
    // ════════════════════════════════════════════════════════════════
    private function seedEmployees(): void
    {
        $list = [
            ['EMP-001','Ahmad Fadzillah bin Kamarudin','820415-12-5677','1982-04-15','male',  'Pengurusan',  'Pengurus Besar / CEO',                12000,'64081234567','A123456789','OG2001234567','married_spouse_working',   2,'Maybank',    '1234 5678 9012'],
            ['EMP-002','Siti Norzahra binti Azman',    '880922-14-4321','1988-09-22','female','Kewangan',    'Pengurus Kewangan',                    7500,'64082345678','A234567890','OG2002345678','married_spouse_not_working',1,'CIMB',       '2345 6789 0123'],
            ['EMP-003','Mohd Rizal bin Othman',         '850630-08-7654','1985-06-30','male',  'Operasi',     'Pengurus Projek Kanan',                8500,'64083456789','A345678901','OG2003456789','married_spouse_working',   3,'Maybank',    '3456 7890 1234'],
            ['EMP-004','Lim Siew Chee',                 '900312-10-1234','1990-03-12','female','Kejuruteraan','Jurutera Perisian Kanan',              7200,'64084567890','A456789012','OG2004567890','single',                   0,'Public Bank','4567 8901 2345'],
            ['EMP-005','Nabilah binti Zainal',          '940815-06-5678','1994-08-15','female','Kejuruteraan','Jurutera Perisian',                    5500,'64085678901','A567890123','OG2005678901','single',                   0,'Maybank',    '5678 9012 3456'],
            ['EMP-006','Rajan a/l Krishnan',            '870220-10-3456','1987-02-20','male',  'Kejuruteraan','Penganalisis Sistem',                  6000,'64086789012','A678901234','OG2006789012','married_spouse_working',   2,'Hong Leong', '6789 0123 4567'],
            ['EMP-007','Wong Kah Wai',                  '920507-14-2345','1992-05-07','male',  'Kejuruteraan','Jurutera Rangkaian',                   5800,'64087890123','A789012345','OG2007890123','single',                   0,'CIMB',       '7890 1234 5678'],
            ['EMP-008','Farhana binti Mohd Noor',       '961105-12-6789','1996-11-05','female','Pentadbiran', 'Eksekutif Pentadbiran',                3800,'64088901234','A890123456','OG2008901234','single',                   0,'Maybank',    '8901 2345 6789'],
            ['EMP-009','Syahir bin Hamdan',              '000312-04-4567','2000-03-12','male',  'Kejuruteraan','Jurutera Perisian (Peringkat Rendah)',3500,'64089012345','A901234567','OG2009012345','single',                   0,'RHB',        '9012 3456 7890'],
            ['EMP-010','Nurul Atiqah binti Rosli',      '981201-06-8901','1998-12-01','female','Jualan',      'Eksekutif Jualan',                     4200,'64080123456','A012345678','OG2010123456','single',                   0,'Maybank',    '0123 4567 8901'],
        ];

        foreach ($list as [
            $no,$name,$ic,$dob,$gender,$dept,$position,
            $basic,$epf,$socso,$taxNo,$marital,$kids,$bank,$acc
        ]) {
            $slug = strtolower(Str::ascii(explode(' ', $name)[0]));
            $this->employees[] = DB::table('employees')->insertGetId([
                'company_id'      => $this->companyId,
                'employee_no'     => $no,
                'name'            => $name,
                'ic_no'           => $ic,
                'email'           => $slug . '@technikamaju.com.my',
                'phone'           => '+601' . rand(1,9) . '-' . rand(100,999) . ' ' . rand(1000,9999),
                'gender'          => $gender,
                'date_of_birth'   => $dob,
                'date_joined'     => '2023-01-02',
                'position'        => $position,
                'department'      => $dept,
                'employment_type' => 'full_time',
                'basic_salary'    => $basic,
                'epf_no'          => $epf,
                'socso_no'        => $socso,
                'income_tax_no'   => $taxNo,
                'marital_status'  => $marital,
                'children_count'  => $kids,
                'is_active'       => true,
                'bank_name'       => $bank,
                'bank_account_no' => $acc,
                'created_at'      => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 7. LEAVE TYPES
    // ════════════════════════════════════════════════════════════════
    private function seedLeaveTypes(): void
    {
        $types = [
            ['annual',   'Cuti Tahunan',           14, true,  true,  5],
            ['sick',     'Cuti Sakit',              14, true,  false, 0],
            ['maternity','Cuti Bersalin',           98, true,  false, 0],
            ['paternity','Cuti Bapa',                7, true,  false, 0],
            ['unpaid',   'Cuti Tanpa Gaji (SLWOP)',  0, false, false, 0],
        ];

        foreach ($types as [$slug,$name,$days,$isPaid,$isCarry,$maxCarry]) {
            $existing = DB::table('leave_types')
                ->where('company_id', $this->companyId)
                ->where('name', $name)->value('id');

            $this->leaveTypes[$slug] = $existing ?: DB::table('leave_types')->insertGetId([
                'company_id'            => $this->companyId,
                'name'                  => $name,
                'days_per_year'         => $days,
                'is_paid'               => $isPaid,
                'is_carry_forward'      => $isCarry,
                'max_carry_forward_days'=> $maxCarry,
                'is_active'             => true,
                'created_at'            => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 8. PAYROLL PERIODS + RUNS + LINES
    // ════════════════════════════════════════════════════════════════
    private function seedPayrollPeriodsAndRuns(): void
    {
        $basics     = [12000,7500,8500,7200,5500,6000,5800,3800,3500,4200];
        $allowances = [500,  300, 400, 300, 200, 200, 200, 150, 150, 150];
        $marital    = ['married_spouse_working','married_spouse_not_working','married_spouse_working',
                       'single','single','married_spouse_working','single','single','single','single'];
        $children   = [2,1,3,0,0,2,0,0,0,0];

        $months = [
            ...array_map(fn($m) => [2025,$m], range(1,12)),
            ...array_map(fn($m) => [2026,$m], range(1,3)),
        ];

        foreach ($months as [$year,$month]) {
            $start   = Carbon::create($year,$month,1);
            $key     = $year . '-' . str_pad($month,2,'0',STR_PAD_LEFT);
            $isDraft = ($year === 2026 && $month === 3);

            $ppId = DB::table('payroll_periods')->insertGetId([
                'company_id'   => $this->companyId,
                'name'         => $start->format('F Y'),
                'year'         => $year,
                'month'        => $month,
                'start_date'   => $start->toDateString(),
                'end_date'     => $start->copy()->endOfMonth()->toDateString(),
                'payment_date' => Carbon::create($year,$month,28)->toDateString(),
                'status'       => $isDraft ? 'open' : 'closed',
                'created_at'   => now(), 'updated_at' => now(),
            ]);
            $this->pPeriods[$key] = $ppId;

            $tGross=$tEmpDed=$tEmrCost=$tNet=$tKWSP=$tSocso=$tEIS=$tPCB = 0;
            $lines = [];

            foreach ($this->employees as $i => $empId) {
                $basic     = $basics[$i];
                $allowance = $allowances[$i];
                $gross     = $basic + $allowance;

                $epfEmp  = round($gross * 0.11, 2);
                $epfEmr  = round($gross * 0.13, 2);
                $sBase   = min($gross,5000);
                $socsoEmp= round($sBase * 0.005,  2);
                $socsoEmr= round($sBase * 0.0175, 2);
                $eBase   = min($gross,5000);
                $eisEmp  = round($eBase * 0.002, 2);
                $eisEmr  = round($eBase * 0.002, 2);
                $pcb     = ($gross > 5000) ? round(($gross-5000)*0.035,2) : 0.00;

                $empDed  = $epfEmp + $socsoEmp + $eisEmp + $pcb;
                $emrCost = $epfEmr + $socsoEmr + $eisEmr;
                $net     = round($gross - $empDed, 2);

                $lines[] = compact('empId','basic','allowance','gross','empDed','emrCost','net',
                                   'year','marital','children','i');

                $tGross+=$gross; $tEmpDed+=$empDed; $tEmrCost+=$emrCost; $tNet+=$net;
                $tKWSP+=$epfEmp+$epfEmr; $tSocso+=$socsoEmp+$socsoEmr;
                $tEIS+=$eisEmp+$eisEmr;  $tPCB+=$pcb;
            }

            $runId = DB::table('payroll_runs')->insertGetId([
                'company_id'               => $this->companyId,
                'payroll_period_id'        => $ppId,
                'period_id'                => $this->periods[$key] ?? null,
                'reference_no'             => 'PAY-' . $key,
                'status'                   => $isDraft ? 'draft' : 'posted',
                'total_gross'              => round($tGross,2),
                'total_employee_deduction' => round($tEmpDed,2),
                'total_employer_cost'      => round($tEmrCost,2),
                'total_net_salary'         => round($tNet,2),
                'total_kwsp'               => round($tKWSP,2),
                'total_socso'              => round($tSocso,2),
                'total_eis'                => round($tEIS,2),
                'total_pcb'                => round($tPCB,2),
                'posted_at'                => $isDraft ? null : now(),
                'created_at'               => now(), 'updated_at' => now(),
            ]);

            foreach ($lines as $l) {
                DB::table('payroll_lines')->insert([
                    'payroll_run_id'           => $runId,
                    'employee_id'              => $l['empId'],
                    'basic_salary'             => $l['basic'],
                    'allowances'               => $l['allowance'],
                    'gross_salary'             => $l['gross'],
                    'total_employee_deduction' => $l['empDed'],
                    'total_employer_cost'      => $l['emrCost'],
                    'net_salary'               => $l['net'],
                    'stat_year'                => $l['year'],
                    'marital_status'           => $l['marital'][$l['i']],
                    'children_count'           => $l['children'][$l['i']],
                    'created_at'               => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 9. STAFF LOANS + REPAYMENTS
    // ════════════════════════════════════════════════════════════════
    private function seedStaffLoans(): void
    {
        $loans = [
            [2,'LOAN-2025-001','personal', 20000.00,36,'2025-01-20','disbursed','Pinjaman Kenderaan – Perodua Myvi 1.5 AV'],
            [4,'LOAN-2025-002','personal',  4000.00,12,'2025-02-10','disbursed','Pinjaman Komputer Riba – Dell Inspiron 15'],
            [6,'LOAN-2025-003','emergency', 3000.00,10,'2025-03-05','disbursed','Pinjaman Kecemasan – Rawatan Perubatan'],
            [8,'LOAN-2025-004','emergency', 5000.00,12,'2025-05-15','disbursed','Pinjaman Kecemasan – Deposit Rumah Sewa'],
            [1,'LOAN-2024-001','personal',  8000.00,24,'2024-06-15','settled',  'Pinjaman Perabot – Fully Settled'],
            [3,'LOAN-2024-002','personal',  3500.00,12,'2024-01-05','settled',  'Pinjaman Komputer – Fully Settled'],
        ];

        foreach ($loans as [$empIdx,$loanNo,$type,$amount,$months,$appliedDate,$status,$notes]) {
            $approved  = Carbon::parse($appliedDate)->addDays(5)->toDateString();
            $disbursed = Carbon::parse($appliedDate)->addDays(10)->toDateString();
            $monthly   = round($amount / $months, 2);

            $loanId = DB::table('staff_loans')->insertGetId([
                'company_id'             => $this->companyId,
                'loan_no'                => $loanNo,
                'employee_id'            => $this->employees[$empIdx],
                'loan_type'              => $type,
                'amount_applied'         => $amount,
                'amount_approved'        => $amount,
                'interest_rate'          => 0.00,
                'tenure_months'          => $months,
                'status'                 => $status,
                'applied_date'           => $appliedDate,
                'approved_date'          => $approved,
                'disbursed_date'         => $disbursed,
                'account_id'             => $this->acct('1180'), // Staff Loans Receivable
                'disbursement_account_id'=> $this->acct('1110'), // Cash at Bank
                'notes'                  => $notes,
                'created_at'             => now(), 'updated_at' => now(),
            ]);

            $balance = $amount;
            for ($i = 1; $i <= $months; $i++) {
                $dueDate = Carbon::parse($disbursed)->addMonths($i)->startOfMonth()->toDateString();
                $isPast  = Carbon::parse($dueDate)->lte(now());
                $isPaid  = ($status === 'settled') || $isPast;
                $balance = round(max(0, $balance - $monthly), 2);

                DB::table('staff_loan_repayments')->insert([
                    'company_id'       => $this->companyId,
                    'staff_loan_id'    => $loanId,
                    'installment_no'   => $i,
                    'due_date'         => $dueDate,
                    'principal_amount' => $monthly,
                    'interest_amount'  => 0.00,
                    'total_amount'     => $monthly,
                    'paid_amount'      => $isPaid ? $monthly : 0,
                    'paid_date'        => $isPaid ? $dueDate : null,
                    'balance_after'    => $balance,
                    'status'           => $isPaid ? 'paid' : 'pending',
                    'created_at'       => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 10. CASH ADVANCES + SETTLEMENTS
    // ════════════════════════════════════════════════════════════════
    private function seedCashAdvances(): void
    {
        $data = [
            [2,'CA-2025-001','Perjalanan ke Kuching – Projek SAINS (5 hari)',   2500.00,'2025-03-08','settled', '2025-03-20',2310.00],
            [0,'CA-2025-002','Bengkel ICT Putrajaya (2 malam + elaun makan)',   1800.00,'2025-04-03','settled', '2025-04-12',1750.00],
            [3,'CA-2025-003','Pembelian Bekalan Projek PETRONAS',                3200.00,'2025-04-30','settled', '2025-05-08',3200.00],
            [5,'CA-2025-004','Perjalanan ke JB – Lawatan Pelanggan Baru',       1500.00,'2025-07-13','settled', '2025-07-22',1380.00],
            [4,'CA-2025-005','Pembelian Komponen Elektronik IoT Fasa 2',         2800.00,'2025-08-30','disbursed',null,       null],
            [9,'CA-2025-006','Latihan Jualan Luar – Ipoh (2 hari)',               950.00,'2025-10-16','approved',null,       null],
            [6,'CA-2026-001','Penginapan & Elaun Lawatan Teknikal Sabah',        2200.00,'2026-01-18','disbursed',null,      null],
            [1,'CA-2026-002','Pembelian Perisian Perakaunan Tambahan',            1200.00,'2026-02-08','approved',null,      null],
            [2,'CA-2026-003','Perjalanan ke Singapura – Mesyuarat Pelanggan',    3500.00,'2026-02-28','disbursed',null,      null],
            [7,'CA-2026-004','Pembelian Kelengkapan Pejabat (Kecemasan)',          680.00,'2026-04-03','draft',   null,      null],
        ];

        foreach ($data as [$empIdx,$no,$purpose,$amount,$applied,$status,$settledDate,$actual]) {
            $approvedDate  = in_array($status,['approved','disbursed','settled'])
                ? Carbon::parse($applied)->addDays(2)->toDateString() : null;
            $disbursedDate = in_array($status,['disbursed','settled'])
                ? Carbon::parse($applied)->addDays(4)->toDateString() : null;
            $dueDate       = $disbursedDate
                ? Carbon::parse($disbursedDate)->addDays(30)->toDateString() : null;

            $caId = DB::table('cash_advances')->insertGetId([
                'company_id'       => $this->companyId,
                'employee_id'      => $this->employees[$empIdx],
                'advance_no'       => $no,
                'purpose'          => $purpose,
                'amount_requested' => $amount,
                'amount_approved'  => in_array($status,['approved','disbursed','settled']) ? $amount : null,
                'amount_settled'   => $actual ?? 0,
                'status'           => $status,
                'applied_date'     => $applied,
                'approved_date'    => $approvedDate,
                'disbursed_date'   => $disbursedDate,
                'due_date'         => $dueDate,
                'notes'            => null,
                'created_at'       => now(), 'updated_at' => now(),
            ]);

            if ($status === 'settled' && $settledDate && $actual !== null) {
                $refund = $amount - $actual;
                DB::table('cash_advance_settlements')->insert([
                    'company_id'      => $this->companyId,
                    'cash_advance_id' => $caId,
                    'settlement_type' => 'expense_claim',
                    'amount'          => $actual,
                    'settlement_date' => $settledDate,
                    'reference_no'    => 'SETTLE-' . strtoupper(Str::random(6)),
                    'notes'           => $refund > 0
                        ? 'Baki pulangan: RM' . number_format($refund,2)
                        : 'Jumlah tepat – tiada baki.',
                    'created_at'      => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 11. LEAVE APPLICATIONS
    // ════════════════════════════════════════════════════════════════
    private function seedLeaveApplications(): void
    {
        $approver = $this->employees[0];

        $data = [
            [0,'annual',   'LA-2025-001','2025-01-27','2025-01-31', 5,'Cuti Tahun Baru Cina bersama keluarga di Penang',     'approved'],
            [1,'sick',     'LA-2025-002','2025-02-03','2025-02-04', 2,'Demam – MC dari Klinik Kesihatan Cheras',             'approved'],
            [2,'annual',   'LA-2025-003','2025-03-10','2025-03-14', 5,'Balik kampung sempena Hari Raya Aidilfitri',          'approved'],
            [3,'annual',   'LA-2025-004','2025-04-21','2025-04-25', 5,'Percutian keluarga ke Langkawi',                     'approved'],
            [4,'sick',     'LA-2025-005','2025-05-08','2025-05-08', 1,'Sakit kepala – MC dari klinik panel',                'approved'],
            [5,'annual',   'LA-2025-006','2025-06-02','2025-06-06', 5,'Pulang ke kampung Negeri Sembilan',                  'approved'],
            [6,'maternity','LA-2025-007','2025-07-01','2025-09-26',60,'Bersalin – anak pertama',                            'approved'],
            [7,'annual',   'LA-2025-008','2025-08-11','2025-08-15', 5,'Cuti Hari Kebangsaan + tahunan (bridge)',            'approved'],
            [8,'sick',     'LA-2025-009','2025-09-10','2025-09-10', 1,'Gastrik akut – MC dari Hospital KL',                'approved'],
            [9,'annual',   'LA-2025-010','2025-10-20','2025-10-24', 5,'Majlis perkahwinan adik di Kelantan',                'approved'],
            [1,'annual',   'LA-2025-011','2025-11-17','2025-11-21', 5,'Percutian ke Singapura',                             'approved'],
            [2,'annual',   'LA-2025-012','2025-12-22','2026-01-02', 9,'Cuti Krismas + Tahun Baru 2026 (bridge leave)',      'approved'],
            [0,'annual',   'LA-2026-001','2026-01-27','2026-01-31', 5,'Cuti Tahun Baru Cina 2026',                          'approved'],
            [4,'sick',     'LA-2026-002','2026-02-12','2026-02-13', 2,'Demam campak – MC dari klinik',                      'approved'],
            [3,'annual',   'LA-2026-003','2026-03-16','2026-03-20', 5,'Percutian keluarga ke Bali',                         'draft'],
            [8,'unpaid',   'LA-2026-004','2026-03-23','2026-03-27', 5,'Urusan peribadi mendesak (cuti tanpa gaji)',         'draft'],
            [9,'annual',   'LA-2026-005','2026-04-06','2026-04-10', 5,'Percutian ke Thailand bersama keluarga',             'draft'],
            [5,'sick',     'LA-2026-006','2026-04-15','2026-04-15', 1,'Pening kepala – MC dari klinik',                    'approved'],
            [7,'annual',   'LA-2026-007','2026-05-19','2026-05-23', 5,'Cuti Wesak + Hari Raya bridge',                     'draft'],
        ];

        foreach ($data as [$empIdx,$leaveSlug,$appNo,$from,$to,$days,$reason,$status]) {
            $ltId = $this->leaveTypes[$leaveSlug] ?? null;
            if (!$ltId) { $this->command->warn("⚠️  Leave type [{$leaveSlug}] skip."); continue; }

            DB::table('leave_applications')->insert([
                'company_id'     => $this->companyId,
                'employee_id'    => $this->employees[$empIdx],
                'leave_type_id'  => $ltId,
                'application_no' => $appNo,
                'start_date'     => $from,
                'end_date'       => $to,
                'total_days'     => $days,
                'reason'         => $reason,
                'status'         => $status,
                'approved_by'    => $status === 'approved' ? $approver : null,
                'approved_date'  => $status === 'approved' ? Carbon::parse($from)->subDays(3)->toDateString() : null,
                'created_at'     => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // 12. BILLPLZ BILLS
    // Note: company_id column is varchar in billplz_bills table
    // ════════════════════════════════════════════════════════════════
    private function seedBillplzBills(): void
    {
        $payers = [
            7 => ['Mohd Hafiz bin Ibrahim', 'hafiz.ibrahim@gmail.com',   '+6012-345 6789'],
            8 => ['Nurul Ain binti Roslan',  'nurulain.roslan@gmail.com', '+6019-876 5432'],
            9 => ['David Lim Chee Wah',      'david.lim@outlook.com',     '+6016-234 5678'],
        ];

        $data = [
            [7,'SAGA-WEB-2025', 'INV-2025-0008','Bayaran Laman Web Peribadi – INV-2025-0008',        4100.00,'paid',   '2025-07-20 14:32:00','TXN202507201432'],
            [8,'SAGA-WEB-2025', 'INV-2026-0012','Bayaran Persediaan E-Dagang – INV-2026-0012',       4800.00,'paid',   '2026-01-15 09:15:00','TXN202601150915'],
            [9,'SAGA-MOB-2026', 'INV-2026-0014','Deposit 50% Aplikasi Mudah Alih – INV-2026-0014',  6000.00,'pending',null,                 null],
            [7,'SAGA-WEB-2025', 'DEP-2026-001', 'Deposit Projek Laman Web v2.0',                     1500.00,'paid',   '2026-03-10 11:20:00','TXN202603101120'],
            [8,'SAGA-HOST-2026','HOST-2026-001','Langganan Pengehosan Tahunan 2026',                  720.00,'due',    null,                 null],
            [9,'SAGA-MOB-2026', 'INV-2026-0014B','Bayaran Baki Milestone 2 – Aplikasi Mudah Alih',  3000.00,'pending',null,                 null],
        ];

        foreach ($data as [$custIdx,$collectionId,$refNo,$desc,$amount,$status,$paidAt,$txnId]) {
            $billplzId = 'bpz_' . strtolower(Str::random(8));
            [$payerName,$payerEmail,$payerPhone] = $payers[$custIdx];
            $isPaid = $status === 'paid';

            DB::table('billplz_bills')->insert([
                'company_id'         => (string) $this->companyId,
                'billplz_id'         => $billplzId,
                'collection_id'      => $collectionId,
                'billable_type'      => 'App\\Models\\Customer',
                'billable_id'        => $this->customers[$custIdx],
                'reference_no'       => $refNo,
                'description'        => $desc,
                'amount'             => $amount,
                'payer_name'         => $payerName,
                'payer_email'        => $payerEmail,
                'payer_phone'        => $payerPhone,
                'status'             => $status,
                'url'                => 'https://www.billplz-sandbox.com/bills/' . $billplzId,
                'paid_at'            => $paidAt,
                'paid_amount'        => $isPaid ? (string) $amount : '0',
                'transaction_id'     => $txnId,
                'transaction_status' => $isPaid ? 'Completed' : null,
                'callback_data'      => json_encode([
                    'id'             => $billplzId,
                    'collection_id'  => $collectionId,
                    'paid'           => $isPaid,
                    'state'          => $status,
                    'amount'         => (int)($amount * 100),
                    'paid_amount'    => $isPaid ? (int)($amount * 100) : 0,
                    'due_at'         => now()->addDays(7)->format('Y-m-d'),
                    'email'          => $payerEmail,
                    'mobile'         => $payerPhone,
                    'sandbox'        => true,
                    'reference_1'    => $refNo,
                    'reference_1_label' => 'Invoice / Reference',
                ]),
                'created_at'         => now(), 'updated_at' => now(),
            ]);
        }
    }
    // ════════════════════════════════════════════════════════════════
    // 13. JOURNALS — replicate InvoiceService::post() + BillService::post()
    //     + PayrollService::post() logic without Auth dependency
    // ════════════════════════════════════════════════════════════════
    private function seedJournals(): void
    {
        $adminId = DB::table('users')->where('company_id', $this->companyId)->value('id') ?? 1;

        // ── Lookup key accounts ──────────────────────────────────────
        // AR: Trade Receivables (1130)
        $arId = $this->acct('1130');
        // AP: Trade Payables (2110)
        $apId = $this->acct('2110');
        // SST Payable (2130)
        $sstPayableId = $this->acct('2130');
        // Cash at Bank (1110) — for payments
        $bankId = $this->acct('1110');

        // Payroll GL mappings (from payroll_gl_mappings table)
        $glMap = [];
        foreach (DB::table('payroll_gl_mappings')->where('company_id', $this->companyId)->get() as $m) {
            $glMap[$m->component] = $m->account_id;
        }

        // ── 1. INVOICE journals (posted invoices only) ───────────────
        $invoices = DB::table('invoices')
            ->where('company_id', $this->companyId)
            ->whereNotIn('status', ['draft', 'void'])
            ->get();

        foreach ($invoices as $inv) {
            $rate     = (float) ($inv->exchange_rate ?? 1.0);
            $currency = $inv->currency_code ?? 'MYR';
            $lines    = DB::table('invoice_lines')->where('invoice_id', $inv->id)->get();

            // Compute base totals — base_subtotal from amount (excl tax)
            $baseSubtotal = 0;
            foreach ($lines as $line) {
                $blt = round((float)$line->base_line_total, 2); // already set, includes tax
                // base_subtotal = sum of (amount * rate), exclude tax
                $baseSubtotal += round((float)$line->amount * $rate, 2);
                DB::table('invoice_lines')->where('id', $line->id)->update([
                    'foreign_unit_price' => $line->unit_price,
                    'foreign_line_total' => $line->line_total,
                    'base_unit_price'    => round((float)$line->unit_price * $rate, 2),
                    'base_line_total'    => round((float)$line->line_total * $rate, 2),
                ]);
            }
            $baseTax   = round((float)$inv->tax_amount * $rate, 2);
            $baseTotal = round($baseSubtotal + $baseTax, 2);

            // Update invoice base totals
            DB::table('invoices')->where('id', $inv->id)->update([
                'foreign_subtotal' => $inv->subtotal,
                'foreign_tax'      => $inv->tax_amount,
                'foreign_total'    => $inv->total,
                'base_subtotal'    => $baseSubtotal,
                'base_tax'         => $baseTax,
                'base_total'       => $baseTotal,
            ]);

            // Create journal header
            $custName = DB::table('customers')->where('id', $inv->customer_id)->value('name');
            $jId = DB::table('journal_headers')->insertGetId([
                'company_id'             => $this->companyId,
                'period_id'              => $inv->period_id,
                'reference_no'           => $inv->invoice_no,
                'date'                   => $inv->date,
                'status'                 => 'posted',
                'source_type'            => 'manual',
                'summary_text'           => 'Invoice ' . $inv->invoice_no . ' — ' . $custName
                                            . ($currency !== 'MYR' ? " ({$currency} @ {$rate})" : ''),
                'exchange_rate'          => $rate,
                'original_currency_code' => $currency,
                'created_by'             => $adminId,
                'posted_by'              => $adminId,
                'posted_at'              => now(),
                'created_at'             => now(), 'updated_at' => now(),
            ]);

            // DR Trade Receivables
            DB::table('journal_lines')->insert([
                'journal_header_id' => $jId,
                'account_id'        => $arId,
                'debit'             => $baseTotal,
                'credit'            => 0,
                'description'       => 'AR — ' . $inv->invoice_no
                                        . ($currency !== 'MYR' ? " ({$currency} {$inv->total} @ {$rate})" : ''),
                'created_at'        => now(), 'updated_at' => now(),
            ]);

            // CR Revenue per line (amount only, excl tax)
            $lines = DB::table('invoice_lines')->where('invoice_id', $inv->id)->get();
            foreach ($lines as $line) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $line->account_id,
                    'debit'             => 0,
                    'credit'            => round((float)$line->amount * $rate, 2),
                    'description'       => $line->description,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR SST Payable
            if ($baseTax > 0 && $sstPayableId) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $sstPayableId,
                    'debit'             => 0,
                    'credit'            => $baseTax,
                    'description'       => 'SST — ' . $inv->invoice_no,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // For paid invoices — add payment journal too
            if (in_array($inv->status, ['paid', 'partial']) && (float)$inv->paid_amount > 0) {
                $paidAmt = (float)$inv->paid_amount;
                $pmtRef  = 'PMT-' . $inv->invoice_no;

                $pjId = DB::table('journal_headers')->insertGetId([
                    'company_id'             => $this->companyId,
                    'period_id'              => $inv->period_id,
                    'reference_no'           => $pmtRef,
                    'date'                   => $inv->due_date,
                    'status'                 => 'posted',
                    'source_type'            => 'manual',
                    'summary_text'           => 'Payment — ' . $inv->invoice_no . ' — ' . $custName,
                    'exchange_rate'          => 1.0,
                    'original_currency_code' => 'MYR',
                    'created_by'             => $adminId,
                    'posted_by'              => $adminId,
                    'posted_at'              => now(),
                    'created_at'             => now(), 'updated_at' => now(),
                ]);

                // DR Bank
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $pjId,
                    'account_id'        => $bankId,
                    'debit'             => $paidAmt,
                    'credit'            => 0,
                    'description'       => 'Payment received — ' . $inv->invoice_no,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);

                // CR AR
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $pjId,
                    'account_id'        => $arId,
                    'debit'             => 0,
                    'credit'            => $paidAmt,
                    'description'       => 'AR settled — ' . $inv->invoice_no,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('   ↳ Invoice journals: ' . count($invoices) . ' invoices.');

        // ── 2. BILL journals (paid bills only) ───────────────────────
        $bills = DB::table('bills')
            ->where('company_id', $this->companyId)
            ->whereIn('status', ['paid', 'approved', 'partial'])
            ->get();

        foreach ($bills as $bill) {
            $rate     = (float)($bill->exchange_rate ?? 1.0);
            $currency = $bill->currency_code ?? 'MYR';
            $lines    = DB::table('bill_lines')->where('bill_id', $bill->id)->get();

            // Compute base totals
            $baseLineTotal = 0;
            foreach ($lines as $line) {
                $blt = round((float)$line->line_total * $rate, 2);
                DB::table('bill_lines')->where('id', $line->id)->update([
                    'foreign_unit_price' => $line->unit_price,
                    'foreign_line_total' => $line->line_total,
                    'base_unit_price'    => round((float)$line->unit_price * $rate, 2),
                    'base_line_total'    => $blt,
                ]);
                $baseLineTotal += $blt;
            }
            $baseTotal = round($baseLineTotal, 2);

            DB::table('bills')->where('id', $bill->id)->update([
                'foreign_subtotal' => $bill->subtotal,
                'foreign_tax'      => 0,
                'foreign_total'    => $bill->total,
                'base_subtotal'    => $baseLineTotal,
                'base_tax'         => 0,
                'base_total'       => $baseTotal,
            ]);

            $vendName = DB::table('vendors')->where('id', $bill->vendor_id)->value('name');
            $jId = DB::table('journal_headers')->insertGetId([
                'company_id'             => $this->companyId,
                'period_id'              => $bill->period_id,
                'reference_no'           => $bill->bill_no,
                'date'                   => $bill->date,
                'status'                 => 'posted',
                'source_type'            => 'manual',
                'summary_text'           => 'Bill ' . $bill->bill_no . ' — ' . $vendName
                                            . ($currency !== 'MYR' ? " ({$currency} @ {$rate})" : ''),
                'exchange_rate'          => $rate,
                'original_currency_code' => $currency,
                'created_by'             => $adminId,
                'posted_by'              => $adminId,
                'posted_at'              => now(),
                'created_at'             => now(), 'updated_at' => now(),
            ]);

            // DR Expense per line
            $lines = DB::table('bill_lines')->where('bill_id', $bill->id)->get();
            foreach ($lines as $line) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $line->account_id,
                    'debit'             => (float)$line->base_line_total,
                    'credit'            => 0,
                    'description'       => $line->description
                                            . ($currency !== 'MYR' ? " ({$currency} {$line->line_total})" : ''),
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR Trade Payables
            DB::table('journal_lines')->insert([
                'journal_header_id' => $jId,
                'account_id'        => $apId,
                'debit'             => 0,
                'credit'            => $baseTotal,
                'description'       => 'AP — ' . $bill->bill_no
                                        . ($currency !== 'MYR' ? " ({$currency} {$bill->total} @ {$rate})" : ''),
                'created_at'        => now(), 'updated_at' => now(),
            ]);

            // Update bill journal_header_id
            DB::table('bills')->where('id', $bill->id)->update([
                'journal_header_id' => $jId,
                'approved_at'       => now(),
                'approved_by'       => $adminId,
            ]);

            // Payment journal for paid bills
            if ($bill->status === 'paid') {
                $pjId = DB::table('journal_headers')->insertGetId([
                    'company_id'             => $this->companyId,
                    'period_id'              => $bill->period_id,
                    'reference_no'           => 'PMT-' . $bill->bill_no,
                    'date'                   => $bill->due_date,
                    'status'                 => 'posted',
                    'source_type'            => 'manual',
                    'summary_text'           => 'Payment — ' . $bill->bill_no . ' — ' . $vendName,
                    'exchange_rate'          => 1.0,
                    'original_currency_code' => 'MYR',
                    'created_by'             => $adminId,
                    'posted_by'              => $adminId,
                    'posted_at'              => now(),
                    'created_at'             => now(), 'updated_at' => now(),
                ]);

                // DR AP
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $pjId,
                    'account_id'        => $apId,
                    'debit'             => $baseTotal,
                    'credit'            => 0,
                    'description'       => 'AP settled — ' . $bill->bill_no,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);

                // CR Bank
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $pjId,
                    'account_id'        => $bankId,
                    'debit'             => 0,
                    'credit'            => $baseTotal,
                    'description'       => 'Payment — ' . $bill->bill_no,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('   ↳ Bill journals: ' . count($bills) . ' bills.');

        // ── 3. PAYROLL journals (posted runs only) ───────────────────
        // Uses payroll_gl_mappings already configured in system
        if (empty($glMap)) {
            $this->command->warn('   ⚠️  PayrollGlMappings kosong — skip payroll journals.');
            return;
        }

        $runs = DB::table('payroll_runs')
            ->where('company_id', $this->companyId)
            ->where('status', 'posted')
            ->get();

        foreach ($runs as $run) {
            $period = DB::table('payroll_periods')->where('id', $run->payroll_period_id)->first();
            if (!$period) continue;

            // ── Recompute statutory figures from payroll_lines ──────────
            // EE deductions only (for CR payables — employee portion)
            // ER contributions (for DR employer expense + CR payables)
            $lines = DB::table('payroll_lines')->where('payroll_run_id', $run->id)->get();

            $totalGross   = 0;
            $kwspEE = $kwspER = $socsoEE = $socsoER = $eisEE = $eisER = $pcb = $net = 0;

            foreach ($lines as $line) {
                $gross = (float)$line->gross_salary;
                $totalGross += $gross;

                // EPF: EE=11%, ER=13% (or 12% if gross>5000)
                $kEE = round($gross * 0.11, 2);
                $kER = round($gross * ($gross <= 5000 ? 0.13 : 0.12), 2);

                // SOCSO: capped at 5000
                $sBase  = min($gross, 5000);
                $sEE    = round($sBase * 0.005, 2);
                $sER    = round($sBase * 0.0175, 2);

                // EIS: capped at 5000
                $eBase  = min($gross, 5000);
                $eEE    = round($eBase * 0.002, 2);
                $eER    = round($eBase * 0.002, 2);

                // PCB simplified
                $linePcb = ($gross > 5000) ? round(($gross - 5000) * 0.035, 2) : 0;

                $empDed = $kEE + $sEE + $eEE + $linePcb;
                $lineNet = round($gross - $empDed, 2);

                $kwspEE  += $kEE;  $kwspER  += $kER;
                $socsoEE += $sEE;  $socsoER += $sER;
                $eisEE   += $eEE;  $eisER   += $eER;
                $pcb     += $linePcb;
                $net     += $lineNet;
            }

            $kwspEE  = round($kwspEE, 2);  $kwspER  = round($kwspER, 2);
            $socsoEE = round($socsoEE, 2); $socsoER = round($socsoER, 2);
            $eisEE   = round($eisEE, 2);   $eisER   = round($eisER, 2);
            $pcb     = round($pcb, 2);
            $net     = round($net, 2);

            // Employer contrib total (extra cost beyond gross)
            $totalEmrContrib = round($kwspER + $socsoER + $eisER, 2);

            // Total DR = gross + employer contrib
            // Total CR = kwsp(EE+ER) + socso(EE+ER) + eis(EE+ER) + pcb + net
            // Verify: gross + emrContrib = net + kwsp_total + socso_total + eis_total + pcb ✓

            $jId = DB::table('journal_headers')->insertGetId([
                'company_id'             => $this->companyId,
                'period_id'              => $run->period_id,
                'reference_no'           => $run->reference_no,
                'date'                   => $period->end_date,
                'status'                 => 'posted',
                'source_type'            => 'manual',
                'summary_text'           => 'Payroll — ' . $period->name,
                'exchange_rate'          => 1.0,
                'original_currency_code' => 'MYR',
                'created_by'             => $adminId,
                'posted_by'              => $adminId,
                'posted_at'              => now(),
                'created_at'             => now(), 'updated_at' => now(),
            ]);

            // DR Salary Expense (gross salaries)
            DB::table('journal_lines')->insert([
                'journal_header_id' => $jId,
                'account_id'        => $glMap['SALARY_EXPENSE'],
                'debit'             => round($totalGross, 2),
                'credit'            => 0,
                'description'       => 'Gross Salary — ' . $period->name,
                'created_at'        => now(), 'updated_at' => now(),
            ]);

            // DR Employer Contribution Expense (EPF ER + SOCSO ER + EIS ER)
            if ($totalEmrContrib > 0) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $glMap['EMPLOYER_CONTRIBUTION_EXPENSE'],
                    'debit'             => $totalEmrContrib,
                    'credit'            => 0,
                    'description'       => 'Employer Contributions (EPF/SOCSO/EIS) — ' . $period->name,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR EPF Payable (EE + ER)
            $kwspTotal = round($kwspEE + $kwspER, 2);
            if ($kwspTotal > 0) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $glMap['KWSP_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => $kwspTotal,
                    'description'       => 'EPF Payable (EE ' . $kwspEE . ' + ER ' . $kwspER . ')',
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR SOCSO Payable (EE + ER)
            $socsoTotal = round($socsoEE + $socsoER, 2);
            if ($socsoTotal > 0) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $glMap['SOCSO_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => $socsoTotal,
                    'description'       => 'SOCSO Payable (EE ' . $socsoEE . ' + ER ' . $socsoER . ')',
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR EIS Payable (EE + ER)
            $eisTotal = round($eisEE + $eisER, 2);
            if ($eisTotal > 0) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $glMap['EIS_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => $eisTotal,
                    'description'       => 'EIS Payable (EE ' . $eisEE . ' + ER ' . $eisER . ')',
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR PCB Payable
            if ($pcb > 0) {
                DB::table('journal_lines')->insert([
                    'journal_header_id' => $jId,
                    'account_id'        => $glMap['PCB_PAYABLE'],
                    'debit'             => 0,
                    'credit'            => $pcb,
                    'description'       => 'PCB/MTD Payable — ' . $period->name,
                    'created_at'        => now(), 'updated_at' => now(),
                ]);
            }

            // CR Net Salary Payable
            DB::table('journal_lines')->insert([
                'journal_header_id' => $jId,
                'account_id'        => $glMap['NET_SALARY_PAYABLE'],
                'debit'             => 0,
                'credit'            => $net,
                'description'       => 'Net Salary Payable — ' . $period->name,
                'created_at'        => now(), 'updated_at' => now(),
            ]);

            // Update payroll run
            DB::table('payroll_runs')->where('id', $run->id)->update([
                'journal_header_id' => $jId,
                'posted_by'         => $adminId,
                'posted_at'         => now(),
            ]);
        }
        $this->command->info('   ↳ Payroll journals: ' . count($runs) . ' runs.');
    }
    // ════════════════════════════════════════════════════════════════
    // 14. PAYROLL LINE DEDUCTIONS
    //     Populate payroll_line_deductions for all seeded payroll lines
    //     using same statutory logic as PayrollService::calculateLine()
    // ════════════════════════════════════════════════════════════════
    private function seedPayrollLineDeductions(): void
    {
        // Clear existing (from seeder previous run)
        DB::table('payroll_line_deductions')
            ->whereIn('payroll_line_id', function ($q) {
                $q->select('payroll_lines.id')
                  ->from('payroll_lines')
                  ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_lines.payroll_run_id')
                  ->where('payroll_runs.company_id', $this->companyId);
            })->delete();

        $lines = DB::table('payroll_lines')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_lines.payroll_run_id')
            ->where('payroll_runs.company_id', $this->companyId)
            ->select('payroll_lines.*', 'payroll_runs.payroll_period_id')
            ->get();

        $count = 0;
        foreach ($lines as $line) {
            $gross = (float) $line->gross_salary;
            $year  = (int) $line->stat_year;

            // ── KWSP ────────────────────────────────────────────────
            $kwspEeRate = 11.0;
            $kwspErRate = $gross <= 5000 ? 13.0 : 12.0;
            $kwspEe     = round($gross * $kwspEeRate / 100, 2);
            $kwspEr     = round($gross * $kwspErRate / 100, 2);

            // ── SOCSO (ceiling RM5,000) ──────────────────────────────
            $sBase      = min($gross, 5000);
            $soCeiling  = $gross > 5000;
            $socsoEe    = $soCeiling ? 24.75  : round($sBase * 0.005,  2);
            $socsoEr    = $soCeiling ? 86.65  : round($sBase * 0.0175, 2);
            $socsoEeRate = 0.5;
            $socsoErRate = 1.75;

            // ── EIS (ceiling RM5,000) ────────────────────────────────
            $eBase      = min($gross, 5000);
            $eisCeiling = $gross > 5000;
            $eisEe      = $eisCeiling ? 9.90 : round($eBase * 0.002, 2);
            $eisEr      = $eisCeiling ? 9.90 : round($eBase * 0.002, 2);
            $eisRate    = 0.2;

            // ── PCB (simplified bracket) ─────────────────────────────
            $marital   = $line->marital_status ?? 'single';
            $children  = (int)($line->children_count ?? 0);
            $annual    = $gross * 12;
            $relief    = 9000
                + ($marital === 'married_spouse_not_working' ? 4000 : 0)
                + ($children * 2000);
            $taxable   = max(0, $annual - $relief);
            $annualTax = $this->calcSimplePcb($taxable);
            $pcb       = round($annualTax / 12, 2);

            $deductions = [
                ['KWSP_EE',  $kwspEe,  $kwspEeRate,  false,        null],
                ['KWSP_ER',  $kwspEr,  $kwspErRate,  false,        null],
                ['SOCSO_EE', $socsoEe, $socsoEeRate, $soCeiling,   null],
                ['SOCSO_ER', $socsoEr, $socsoErRate, $soCeiling,   null],
                ['EIS_EE',   $eisEe,   $eisRate,     $eisCeiling,  null],
                ['EIS_ER',   $eisEr,   $eisRate,     $eisCeiling,  null],
                ['PCB',      $pcb,     null,         false,        $taxable],
            ];

            foreach ($deductions as [$component, $amount, $rate, $ceiling, $taxableIncome]) {
                DB::table('payroll_line_deductions')->insert([
                    'payroll_line_id' => $line->id,
                    'component'       => $component,
                    'amount'          => round($amount, 2),
                    'rate_used'       => $rate,
                    'ceiling_applied' => $ceiling,
                    'taxable_income'  => $taxableIncome,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // ── Update payroll_line totals ───────────────────────────
            $empDed  = round($kwspEe + $socsoEe + $eisEe + $pcb, 2);
            $emrCost = round($gross + $kwspEr + $socsoEr + $eisEr, 2);
            $net     = round($gross - $empDed, 2);

            DB::table('payroll_lines')->where('id', $line->id)->update([
                'total_employee_deduction' => $empDed,
                'total_employer_cost'      => $emrCost,
                'net_salary'               => $net,
                'updated_at'               => now(),
            ]);

            $count++;
        }

        // ── Recalculate payroll_runs totals ──────────────────────────
        $runs = DB::table('payroll_runs')
            ->where('company_id', $this->companyId)
            ->get();

        foreach ($runs as $run) {
            $runLines = DB::table('payroll_lines')
                ->where('payroll_run_id', $run->id)->get();

            $tGross = $tEmpDed = $tEmrCost = $tNet = 0;
            $tKwsp  = $tSocso  = $tEis     = $tPcb = 0;

            foreach ($runLines as $l) {
                $tGross   += (float)$l->gross_salary;
                $tEmpDed  += (float)$l->total_employee_deduction;
                $tEmrCost += (float)$l->total_employer_cost;
                $tNet     += (float)$l->net_salary;
            }

            // Sum from deductions table
            $deds = DB::table('payroll_line_deductions')
                ->join('payroll_lines', 'payroll_lines.id', '=', 'payroll_line_deductions.payroll_line_id')
                ->where('payroll_lines.payroll_run_id', $run->id)
                ->select('payroll_line_deductions.component', DB::raw('SUM(payroll_line_deductions.amount) as total'))
                ->groupBy('payroll_line_deductions.component')
                ->get()->keyBy('component');

            $tKwsp  = round(($deds['KWSP_EE']->total  ?? 0) + ($deds['KWSP_ER']->total  ?? 0), 2);
            $tSocso = round(($deds['SOCSO_EE']->total ?? 0) + ($deds['SOCSO_ER']->total ?? 0), 2);
            $tEis   = round(($deds['EIS_EE']->total   ?? 0) + ($deds['EIS_ER']->total   ?? 0), 2);
            $tPcb   = round($deds['PCB']->total ?? 0, 2);

            DB::table('payroll_runs')->where('id', $run->id)->update([
                'total_gross'              => round($tGross, 2),
                'total_employee_deduction' => round($tEmpDed, 2),
                'total_employer_cost'      => round($tEmrCost, 2),
                'total_net_salary'         => round($tNet, 2),
                'total_kwsp'               => $tKwsp,
                'total_socso'              => $tSocso,
                'total_eis'                => $tEis,
                'total_pcb'                => $tPcb,
                'updated_at'               => now(),
            ]);
        }

        $this->command->info("   ↳ Payroll line deductions: {$count} lines populated.");
    }

    // ── PCB simplified bracket (LHDN 2025/2026) ─────────────────────
    private function calcSimplePcb(float $taxable): float
    {
        // Malaysian income tax brackets (resident individual)
        $brackets = [
            [0,       5000,   0,    0],
            [5001,    20000,  0,    1],
            [20001,   35000,  150,  3],
            [35001,   50000,  600,  8],
            [50001,   70000,  1800, 13],
            [70001,   100000, 4400, 21],
            [100001,  400000, 10700,24],
            [400001,  600000, 82700,24.5],
            [600001,  2000000,131700,25],
            [2000001, PHP_INT_MAX, 481700, 26],
        ];

        foreach ($brackets as [$from, $to, $base, $rate]) {
            if ($taxable >= $from && $taxable <= $to) {
                return $base + round(($taxable - $from) * $rate / 100, 2);
            }
        }
        return 0;
    }
}
