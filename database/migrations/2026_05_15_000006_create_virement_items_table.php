<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // 'from' = akaun yang dipindahkan DARI (dikurangkan)
            // 'to'   = akaun yang diterima (ditambah)
            $table->enum('direction', ['from', 'to']);

            $table->foreignId('budget_item_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['virement_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virement_items');
    }
};
