<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate');
            $table->foreignId('rate_overridden_by')->nullable()->after('override_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rate_overridden_at')->nullable()->after('rate_overridden_by');
        });

        Schema::table('bill_lines', function (Blueprint $table) {
            $table->decimal('base_unit_price', 18, 2)->nullable()->after('foreign_unit_price');
            $table->decimal('base_line_total', 18, 2)->nullable()->after('foreign_line_total');
        });
    }

    public function down(): void
    {
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->dropColumn(['base_unit_price', 'base_line_total']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['rate_overridden_by']);
            $table->dropColumn(['exchange_rate_date', 'rate_overridden_by', 'rate_overridden_at']);
        });
    }
};
