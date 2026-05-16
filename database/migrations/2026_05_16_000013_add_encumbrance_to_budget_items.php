<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori 2 requirement:
     * budget_items needs encumbrance tracking columns.
     * When LO is approved → encumbered_amount increases.
     * When GRN posted → encumbered_amount decreases, actual_spent increases.
     *
     * Available balance = balance_amount - encumbered_amount
     */
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            // Encumbrance = committed but not yet spent (LO approved, GRN not yet done)
            $table->decimal('encumbered_amount', 15, 2)->default(0)->after('actual_spent');

            // True available = balance - encumbered
            // (balance_amount = allocated - actual_spent, defined in Kategori 1)
            // available_to_commit = balance_amount - encumbered_amount
            // We don't store this as stored column to avoid dependency hell;
            // compute in BudgetService::getAvailableBalance()
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn('encumbered_amount');
        });
    }
};
