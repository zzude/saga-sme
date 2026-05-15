<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Link ke COA (asset account)
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            // Kod akaun bank kerajaan (e.g. "A001", "B/AGF/2026")
            $table->string('gov_account_code', 50)->nullable();

            // Nama akaun bank
            $table->string('account_name', 150);

            // Nombor akaun bank
            $table->string('account_number', 50);

            // Bank
            $table->string('bank_name', 100);
            $table->string('bank_branch', 100)->nullable();
            $table->string('swift_code', 20)->nullable();

            // Jenis akaun: caruman | tabung_khas | am | projek | gaji
            $table->string('account_type', 30)->default('am');

            // Matawang
            $table->string('currency', 3)->default('MYR');

            // Baki (dikemas kini dari GL)
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamp('balance_updated_at')->nullable();

            // Status akaun
            $table->boolean('is_active')->default(true);

            // Had overdraf (jika ada)
            $table->decimal('overdraft_limit', 15, 2)->default(0);

            // Nota
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'account_number']);
            $table->index(['company_id', 'is_active', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_bank_accounts');
    }
};
