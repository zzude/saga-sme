<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warrant_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warrant_allocation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->decimal('warrant_amount', 15, 2)->default(0);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warrant_allocation_id', 'budget_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warrant_items');
    }
};
