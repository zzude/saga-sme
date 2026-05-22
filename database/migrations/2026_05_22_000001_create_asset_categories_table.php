<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');                          // e.g. "Kenderaan", "Peralatan Pejabat"
            $table->integer('useful_life_years')->default(5);
            $table->enum('depreciation_method', [
                'straight_line',
                'reducing_balance',
            ])->default('straight_line');

            // Linked COA accounts
            $table->unsignedBigInteger('asset_account_id')->nullable();                  // e.g. 1500 Fixed Assets
            $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable(); // e.g. 1510 Accum Dep
            $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();    // e.g. 6200 Dep Expense

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('asset_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('accumulated_depreciation_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('depreciation_expense_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
