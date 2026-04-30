<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices — only truly missing columns
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate');
            $table->foreignId('rate_overridden_by')->nullable()->after('override_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rate_overridden_at')->nullable()->after('rate_overridden_by');
        });

        // invoice_lines — base MYR columns
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->decimal('base_unit_price', 18, 2)->nullable()->after('foreign_unit_price');
            $table->decimal('base_line_total', 18, 2)->nullable()->after('foreign_line_total');
        });

        // journal_headers — FX metadata
        Schema::table('journal_headers', function (Blueprint $table) {
            $table->char('currency_id', 3)->nullable()->after('id');
            $table->foreign('currency_id')->references('code')->on('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency_id');
            $table->char('original_currency_code', 3)->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('journal_headers', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'original_currency_code']);
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['base_unit_price', 'base_line_total']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['rate_overridden_by']);
            $table->dropColumn(['exchange_rate_date', 'rate_overridden_by', 'rate_overridden_at']);
        });
    }
};
