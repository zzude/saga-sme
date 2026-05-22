<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('period_id')->nullable();
            $table->date('depreciation_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('fixed_assets')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('accounting_periods')->nullOnDelete();
            $table->foreign('journal_id')->references('id')->on('journal_headers')->nullOnDelete();

            $table->unique(['asset_id', 'period_id'], 'unique_asset_period_depreciation');
            $table->index(['company_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
