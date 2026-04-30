<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            // Active — Phase 5.3 launch currencies
            [
                'code'           => 'MYR',
                'name'           => 'Malaysian Ringgit',
                'symbol'         => 'RM',
                'decimal_places' => 2,
                'is_active'      => true,
            ],
            [
                'code'           => 'USD',
                'name'           => 'US Dollar',
                'symbol'         => '$',
                'decimal_places' => 2,
                'is_active'      => true,
            ],
            [
                'code'           => 'SGD',
                'name'           => 'Singapore Dollar',
                'symbol'         => 'S$',
                'decimal_places' => 2,
                'is_active'      => true,
            ],

            // Inactive — future-ready, activate when needed
            [
                'code'           => 'EUR',
                'name'           => 'Euro',
                'symbol'         => '€',
                'decimal_places' => 2,
                'is_active'      => false,
            ],
            [
                'code'           => 'GBP',
                'name'           => 'British Pound',
                'symbol'         => '£',
                'decimal_places' => 2,
                'is_active'      => false,
            ],
            [
                'code'           => 'AUD',
                'name'           => 'Australian Dollar',
                'symbol'         => 'A$',
                'decimal_places' => 2,
                'is_active'      => false,
            ],
            [
                'code'           => 'JPY',
                'name'           => 'Japanese Yen',
                'symbol'         => '¥',
                'decimal_places' => 0,
                'is_active'      => false,
            ],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                array_merge($currency, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Currencies seeded: MYR, USD, SGD (active) | EUR, GBP, AUD, JPY (inactive)');
    }
}