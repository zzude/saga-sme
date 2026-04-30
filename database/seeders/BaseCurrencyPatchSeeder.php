<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaseCurrencyPatchSeeder extends Seeder
{
    public function run(): void
    {
        // Patch all existing companies — set base_currency to MYR
        // Safe to run multiple times (idempotent)
        $affected = DB::table('companies')
            ->whereNull('base_currency')
            ->orWhere('base_currency', '')
            ->update([
                'base_currency' => 'MYR',
                'updated_at'    => now(),
            ]);

        $total = DB::table('companies')->count();

        $this->command->info("✅ Companies patched: {$affected} updated, {$total} total (all should be MYR)");
    }
}