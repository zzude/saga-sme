<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('quotation_id');

            $table->integer('line_no');
            $table->string('description');
            $table->text('detail')->nullable();           // additional detail / spec
            $table->string('unit_of_measure')->default('unit'); // unit, kg, m, set, lot, etc.

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('gross_amount', 15, 2);       // qty * unit_price

            // Discount per line
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0); // computed
            $table->decimal('net_amount', 15, 2);         // gross - discount

            // SST per line
            $table->boolean('is_sst_applicable')->default(false);
            $table->decimal('sst_rate', 5, 2)->default(0);
            $table->decimal('sst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);       // net + sst

            // Optional: link to inventory item (Phase 2)
            $table->unsignedBigInteger('item_id')->nullable();

            $table->timestamps();

            $table->foreign('quotation_id')
                ->references('id')->on('quotations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
