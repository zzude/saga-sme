<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('item_id');

            $table->enum('type', [
                'in',           // purchase / stock received
                'out',          // sale / stock issued
                'adjustment',   // manual adjustment
                'opening',      // opening stock entry
            ]);

            $table->decimal('quantity', 15, 2);       // positive always
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2);  // stock balance after this movement

            // Polymorphic reference — link to source document
            $table->string('reference_type')->nullable();   // e.g. App\Models\Invoice
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('reference_no')->nullable();     // human readable e.g. INV-2026-00001
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['company_id', 'item_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
