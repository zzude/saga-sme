<?php

namespace Database\Seeders;

use App\Models\TaxCode;
use Illuminate\Database\Seeder;

class TaxCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['code' => 'SR',  'description' => 'Standard Rated Service Tax', 'tax_type' => 'service',      'rate' => 8.00,  'effective_from' => '2024-03-01'],
            ['code' => 'ZR',  'description' => 'Zero Rated',                 'tax_type' => 'service',      'rate' => 0.00,  'effective_from' => null],
            ['code' => 'EX',  'description' => 'Exempt',                     'tax_type' => 'exempt',       'rate' => 0.00,  'effective_from' => null],
            ['code' => 'OS',  'description' => 'Out of Scope',               'tax_type' => 'out_of_scope', 'rate' => 0.00,  'effective_from' => null],
            ['code' => 'ST5', 'description' => 'Sales Tax 5%',               'tax_type' => 'sales',        'rate' => 5.00,  'effective_from' => null],
            ['code' => 'ST10','description' => 'Sales Tax 10%',              'tax_type' => 'sales',        'rate' => 10.00, 'effective_from' => null],
        ];

        foreach ($codes as $code) {
            TaxCode::firstOrCreate(
                ['code' => $code['code'], 'company_id' => null],
                array_merge($code, ['company_id' => null, 'is_active' => true])
            );
        }
    }
}