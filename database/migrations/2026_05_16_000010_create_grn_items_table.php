<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();
            $table->unsignedBigInteger('goods_received_note_id');
            $table->unsignedBigInteger('lo_item_id'); // 3-way match: PR → LO → GRN

            $table->integer('line_no');
            $table->string('description');
            $table->string('unit_of_measure')->default('unit');

            $table->decimal('quantity_ordered', 10, 2);   // from LO
            $table->decimal('quantity_received', 10, 2);  // actual received
            $table->decimal('quantity_rejected', 10, 2)->default(0);
            $table->decimal('quantity_accepted', 10, 2)->storedAs('quantity_received - quantity_rejected');

            $table->decimal('unit_price', 15, 2);  // from LO
            $table->decimal('accepted_amount', 15, 2)->storedAs('quantity_accepted * unit_price');

            // Condition
            $table->enum('condition', [
                'good',
                'damaged',
                'partial',
            ])->default('good');

            $table->text('condition_notes')->nullable();
            $table->string('serial_number')->nullable();     // for assets
            $table->string('batch_number')->nullable();       // for consumables

            $table->timestamps();

            $table->foreign('goods_received_note_id')
                ->references('id')->on('goods_received_notes')
                ->onDelete('cascade');

            $table->foreign('lo_item_id')
                ->references('id')->on('lo_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
