<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pr_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();
            $table->unsignedBigInteger('purchase_requisition_id');

            $table->integer('line_no');
            $table->string('description');
            $table->string('unit_of_measure')->default('unit'); // unit, kg, liter, set, etc.
            $table->decimal('quantity', 10, 2);
            $table->decimal('estimated_unit_price', 15, 2);
            $table->decimal('estimated_total', 15, 2)->storedAs('quantity * estimated_unit_price');

            // Specs / purpose
            $table->text('specification')->nullable();
            $table->string('purpose')->nullable();

            // Linked to GRN when received
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->decimal('quantity_pending', 10, 2)->storedAs('quantity - quantity_received');

            $table->timestamps();

            $table->foreign('purchase_requisition_id')
                ->references('id')->on('purchase_requisitions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_items');
    }
};
