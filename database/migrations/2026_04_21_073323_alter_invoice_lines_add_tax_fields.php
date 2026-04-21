<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->foreignId('tax_code_id')->nullable()->after('account_id')
                  ->constrained('tax_codes')->nullOnDelete();
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['tax_code_id']);
            $table->dropColumn(['tax_code_id', 'tax_rate']);
        });
    }
};