<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Link ke COA (account_id dari chart_of_accounts)
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            // Kategori perbelanjaan gov: emolumen | perkhidmatan | bekalan | aset | pinjaman | lain-lain
            $table->string('object_class', 50)->nullable();

            // Kod objek (e.g. "11000" = Gaji Tetap)
            $table->string('object_code', 20)->nullable();

            // Penerangan item
            $table->string('description', 300);

            // Anggaran asal
            $table->decimal('original_amount', 15, 2)->default(0);

            // Selepas viremen / tambahan
            $table->decimal('revised_amount', 15, 2)->default(0);

            // Jumlah dah diperuntukkan (waran)
            $table->decimal('allocated_amount', 15, 2)->default(0);

            // Jumlah dah dibelanjakan (actual dari GL)
            $table->decimal('actual_amount', 15, 2)->default(0);

            // Baki
            $table->decimal('balance_amount', 15, 2)->default(0);

            // Urutan paparan
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['annual_budget_id', 'account_id']);
            $table->index(['company_id', 'object_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
