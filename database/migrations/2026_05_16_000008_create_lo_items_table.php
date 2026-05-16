<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lo_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();
            $table->unsignedBigInteger('local_order_id');

            // Link back to PR item
            $table->unsignedBigInteger('pr_item_id')->nullable();

            $table->integer('line_no');
            $table->string('description');
            $table->string('unit_of_measure')->default('unit');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2)->storedAs('quantity * unit_price');

            // SST / Tax
            $table->decimal('sst_rate', 5, 2)->default(0);
            $table->decimal('sst_amount', 15, 2)->default(0);
            $table->decimal('total_with_sst', 15, 2)->storedAs('total_price + sst_amount');

            // Receiving tracking (from GRN)
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->decimal('quantity_pending', 10, 2)->storedAs('quantity - quantity_received');

            $table->string('specification')->nullable();

            $table->timestamps();

            $table->foreign('local_order_id')
                ->references('id')->on('local_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lo_items');
    }
};
