<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Company;

class CoaSeederService
{
    public function seedForCompany(Company $company, string $tier = 'standard'): void
    {
        // Kalau dah ada accounts untuk company ni — skip
        if (Account::where('company_id', $company->id)->exists()) {
            return;
        }

        $accounts = $this->getAccounts($tier);

        foreach ($accounts as $account) {
            Account::create([
                'company_id'  => $company->id,
                'code'        => $account['code'],
                'name'        => $account['name'],
                'type'        => $account['type'],
                'level'       => $account['level'] ?? 1,
                'is_active'   => true,
            ]);
        }
    }

    private function getAccounts(string $tier): array
    {
        return match($tier) {
            'minimal'  => $this->minimalAccounts(),
            'extended' => $this->standardAccounts(), // extended = standard for now
            default    => $this->standardAccounts(),
        };
    }

    private function minimalAccounts(): array
    {
        return [
            // Aset
            ['code' => '1100', 'name' => 'Tunai & Bank',           'type' => 'asset'],
            ['code' => '1200', 'name' => 'Akaun Belum Terima',     'type' => 'asset'],
            ['code' => '1300', 'name' => 'Inventori',              'type' => 'asset'],
            ['code' => '1500', 'name' => 'Aset Tetap',             'type' => 'asset'],
            // Liabiliti
            ['code' => '2100', 'name' => 'Akaun Belum Bayar',      'type' => 'liability'],
            ['code' => '2200', 'name' => 'Pinjaman',               'type' => 'liability'],
            ['code' => '2300', 'name' => 'SST Perlu Dibayar',      'type' => 'liability'],
            // Ekuiti
            ['code' => '3100', 'name' => 'Modal',                  'type' => 'equity'],
            ['code' => '3200', 'name' => 'Keuntungan Tertahan',    'type' => 'equity'],
            // Pendapatan
            ['code' => '4100', 'name' => 'Jualan',                 'type' => 'revenue'],
            ['code' => '4200', 'name' => 'Pendapatan Lain',        'type' => 'revenue'],
            // Perbelanjaan
            ['code' => '5100', 'name' => 'Kos Jualan',             'type' => 'expense'],
            ['code' => '5200', 'name' => 'Gaji',                   'type' => 'expense'],
            ['code' => '5300', 'name' => 'Sewa',                   'type' => 'expense'],
            ['code' => '5400', 'name' => 'Utiliti',                'type' => 'expense'],
            ['code' => '5900', 'name' => 'Perbelanjaan Am',        'type' => 'expense'],
        ];
    }

    private function standardAccounts(): array
    {
        return [
            // ── ASET SEMASA ──────────────────────────────────
            ['code' => '1010', 'name' => 'Tunai',                          'type' => 'asset'],
            ['code' => '1020', 'name' => 'Akaun Bank Semasa',              'type' => 'asset'],
            ['code' => '1030', 'name' => 'Akaun Bank Simpanan',            'type' => 'asset'],
            ['code' => '1040', 'name' => 'Wang Runcit',                    'type' => 'asset'],
            ['code' => '1110', 'name' => 'Akaun Belum Terima — Perdagangan','type' => 'asset'],
            ['code' => '1120', 'name' => 'Akaun Belum Terima — Lain',      'type' => 'asset'],
            ['code' => '1130', 'name' => 'Elaun Hutang Ragu',              'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventori — Barang Siap',        'type' => 'asset'],
            ['code' => '1210', 'name' => 'Inventori — Bahan Mentah',       'type' => 'asset'],
            ['code' => '1220', 'name' => 'Inventori — Kerja Dalam Proses', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Cukai Dibayar Pendahuluan',      'type' => 'asset'],
            ['code' => '1310', 'name' => 'SST Input (Dibayar)',            'type' => 'asset'],
            ['code' => '1320', 'name' => 'Deposit Dibayar',                'type' => 'asset'],
            ['code' => '1330', 'name' => 'Perbelanjaan Prabayar',          'type' => 'asset'],
            ['code' => '1340', 'name' => 'Pinjaman Kepada Pekerja',        'type' => 'asset'],
            // ── ASET TIDAK SEMASA ────────────────────────────
            ['code' => '1510', 'name' => 'Tanah & Bangunan',               'type' => 'asset'],
            ['code' => '1520', 'name' => 'Kelengkapan & Peralatan',        'type' => 'asset'],
            ['code' => '1530', 'name' => 'Perabot & Hiasan',               'type' => 'asset'],
            ['code' => '1540', 'name' => 'Kenderaan Bermotor',             'type' => 'asset'],
            ['code' => '1550', 'name' => 'Komputer & Perisian',            'type' => 'asset'],
            ['code' => '1560', 'name' => 'Susutnilai Terkumpul',           'type' => 'asset'],
            ['code' => '1600', 'name' => 'Pelaburan Jangka Panjang',       'type' => 'asset'],
            ['code' => '1700', 'name' => 'Aset Tidak Ketara',              'type' => 'asset'],
            // ── LIABILITI SEMASA ─────────────────────────────
            ['code' => '2010', 'name' => 'Akaun Belum Bayar — Perdagangan','type' => 'liability'],
            ['code' => '2020', 'name' => 'Akaun Belum Bayar — Lain',       'type' => 'liability'],
            ['code' => '2030', 'name' => 'Akruan Perbelanjaan',            'type' => 'liability'],
            ['code' => '2040', 'name' => 'Deposit Diterima',               'type' => 'liability'],
            ['code' => '2050', 'name' => 'Pendapatan Tertangguh',          'type' => 'liability'],
            ['code' => '2100', 'name' => 'SST Output (Dikutip)',           'type' => 'liability'],
            ['code' => '2110', 'name' => 'Cukai Perkhidmatan 8%',         'type' => 'liability'],
            ['code' => '2120', 'name' => 'Cukai Jualan',                   'type' => 'liability'],
            ['code' => '2200', 'name' => 'KWSP Perlu Dibayar',             'type' => 'liability'],
            ['code' => '2210', 'name' => 'SOCSO Perlu Dibayar',            'type' => 'liability'],
            ['code' => '2220', 'name' => 'EIS Perlu Dibayar',              'type' => 'liability'],
            ['code' => '2230', 'name' => 'PCB / MTD Perlu Dibayar',        'type' => 'liability'],
            ['code' => '2300', 'name' => 'Pinjaman Bank Jangka Pendek',    'type' => 'liability'],
            ['code' => '2310', 'name' => 'Overdraf Bank',                  'type' => 'liability'],
            ['code' => '2400', 'name' => 'Dividen Belum Dibayar',          'type' => 'liability'],
            // ── LIABILITI TIDAK SEMASA ───────────────────────
            ['code' => '2500', 'name' => 'Pinjaman Bank Jangka Panjang',   'type' => 'liability'],
            ['code' => '2510', 'name' => 'Pajakan Kewangan',               'type' => 'liability'],
            // ── EKUITI ───────────────────────────────────────
            ['code' => '3100', 'name' => 'Modal Berbayar',                 'type' => 'equity'],
            ['code' => '3110', 'name' => 'Premium Saham',                  'type' => 'equity'],
            ['code' => '3200', 'name' => 'Keuntungan Tertahan',            'type' => 'equity'],
            ['code' => '3210', 'name' => 'Rizab Am',                       'type' => 'equity'],
            ['code' => '3300', 'name' => 'Ambilan Pemilik',                'type' => 'equity'],
            // ── PENDAPATAN ───────────────────────────────────
            ['code' => '4100', 'name' => 'Jualan — Produk',                'type' => 'revenue'],
            ['code' => '4110', 'name' => 'Jualan — Perkhidmatan',          'type' => 'revenue'],
            ['code' => '4120', 'name' => 'Jualan — Projek',                'type' => 'revenue'],
            ['code' => '4200', 'name' => 'Pulangan & Elaun Jualan',        'type' => 'revenue'],
            ['code' => '4300', 'name' => 'Pendapatan Faedah',              'type' => 'revenue'],
            ['code' => '4310', 'name' => 'Pendapatan Dividen',             'type' => 'revenue'],
            ['code' => '4320', 'name' => 'Keuntungan Pelupusan Aset',      'type' => 'revenue'],
            ['code' => '4400', 'name' => 'Pendapatan Lain-lain',           'type' => 'revenue'],
            // ── KOS JUALAN ───────────────────────────────────
            ['code' => '5010', 'name' => 'Kos Barang Dijual',              'type' => 'expense'],
            ['code' => '5020', 'name' => 'Kos Bahan Mentah',               'type' => 'expense'],
            ['code' => '5030', 'name' => 'Kos Buruh Langsung',             'type' => 'expense'],
            ['code' => '5040', 'name' => 'Overhed Pengeluaran',            'type' => 'expense'],
            // ── PERBELANJAAN OPERASI ─────────────────────────
            ['code' => '5100', 'name' => 'Gaji & Upah',                    'type' => 'expense'],
            ['code' => '5110', 'name' => 'Caruman KWSP (Majikan)',          'type' => 'expense'],
            ['code' => '5120', 'name' => 'Caruman SOCSO (Majikan)',         'type' => 'expense'],
            ['code' => '5130', 'name' => 'Caruman EIS (Majikan)',           'type' => 'expense'],
            ['code' => '5140', 'name' => 'Elaun Pekerja',                  'type' => 'expense'],
            ['code' => '5150', 'name' => 'Latihan & Pembangunan',          'type' => 'expense'],
            ['code' => '5200', 'name' => 'Sewa Premis',                    'type' => 'expense'],
            ['code' => '5210', 'name' => 'Utiliti (Elektrik, Air)',         'type' => 'expense'],
            ['code' => '5220', 'name' => 'Telekomunikasi & Internet',       'type' => 'expense'],
            ['code' => '5230', 'name' => 'Penyelenggaraan & Pembaikan',    'type' => 'expense'],
            ['code' => '5300', 'name' => 'Pemasaran & Pengiklanan',        'type' => 'expense'],
            ['code' => '5310', 'name' => 'Perjalanan & Pengangkutan',      'type' => 'expense'],
            ['code' => '5320', 'name' => 'Hiburan & Penjamu',              'type' => 'expense'],
            ['code' => '5400', 'name' => 'Insurans',                       'type' => 'expense'],
            ['code' => '5410', 'name' => 'Lesen & Permit',                 'type' => 'expense'],
            ['code' => '5420', 'name' => 'Yuran Profesional',              'type' => 'expense'],
            ['code' => '5430', 'name' => 'Yuran Audit & Perakaunan',       'type' => 'expense'],
            ['code' => '5440', 'name' => 'Yuran Guaman',                   'type' => 'expense'],
            ['code' => '5500', 'name' => 'Susutnilai Aset',                'type' => 'expense'],
            ['code' => '5510', 'name' => 'Pelunasan Aset Tidak Ketara',    'type' => 'expense'],
            ['code' => '5600', 'name' => 'Perbelanjaan Faedah',            'type' => 'expense'],
            ['code' => '5610', 'name' => 'Caj Bank',                       'type' => 'expense'],
            ['code' => '5700', 'name' => 'Hutang Lapuk',                   'type' => 'expense'],
            ['code' => '5800', 'name' => 'Cukai Pendapatan',               'type' => 'expense'],
            ['code' => '5900', 'name' => 'Perbelanjaan Am & Pentadbiran',  'type' => 'expense'],
            ['code' => '5910', 'name' => 'Alat Tulis & Pejabat',           'type' => 'expense'],
            ['code' => '5920', 'name' => 'Perbelanjaan Komputer',          'type' => 'expense'],
            ['code' => '5930', 'name' => 'Perbelanjaan Lain-lain',         'type' => 'expense'],
        ];
    }
}
