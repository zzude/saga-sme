<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            ChartOfAccountsSeeder::class,
            CurrencySeeder::class,          // seed currencies table
            BaseCurrencyPatchSeeder::class, // patch companies.base_currency = MYR            
        ]);       

    }
}
