<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices — add missing FX fields only
        // (currency_code and exchange_rate already exist)
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('rate_source', ['AUTO', 'MANUAL', 'OVERRIDE'])->default('AUTO')->after('exchange_rate');
            $table->string('override_reason', 255)->nullable()->after('rate_source');

            // Foreign currency amounts
            $table->decimal('foreign_subtotal', 18, 2)->nullable()->after('override_reason');
            $table->decimal('foreign_tax', 18, 2)->nullable()->after('foreign_subtotal');
            $table->decimal('foreign_total', 18, 2)->nullable()->after('foreign_tax');

            // MYR base amounts (immutable, locked at posting time)
            $table->decimal('base_subtotal', 18, 2)->nullable()->after('foreign_total');
            $table->decimal('base_tax', 18, 2)->nullable()->after('base_subtotal');
            $table->decimal('base_total', 18, 2)->nullable()->after('base_tax');
        });

        // invoice_lines — add FX fields at line level (needed for PDF display)
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->decimal('foreign_unit_price', 18, 2)->nullable()->after('unit_price');
            $table->decimal('foreign_line_total', 18, 2)->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['foreign_unit_price', 'foreign_line_total']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'rate_source', 'override_reason',
                'foreign_subtotal', 'foreign_tax', 'foreign_total',
                'base_subtotal', 'base_tax', 'base_total',
            ]);
        });
    }
};