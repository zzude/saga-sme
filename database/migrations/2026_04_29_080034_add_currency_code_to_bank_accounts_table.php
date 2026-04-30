<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // accounts (COA) — add currency_code for bank-type accounts
        // Non-bank accounts will remain NULL (MYR assumed)
        Schema::table('accounts', function (Blueprint $table) {
            $table->char('currency_code', 3)->nullable()->default(null)->after('is_active');

            $table->foreign('currency_code')->references('code')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['currency_code']);
            $table->dropColumn('currency_code');
        });
    }
};