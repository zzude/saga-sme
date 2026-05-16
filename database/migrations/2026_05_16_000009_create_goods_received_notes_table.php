<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            $table->string('grn_number')->unique(); // e.g. GRN-2026-00001
            $table->unsignedBigInteger('local_order_id');
            $table->date('received_date');

            // Vendor info
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_delivery_note')->nullable(); // vendor's DO number
            $table->date('vendor_delivery_date')->nullable();

            // Receiver
            $table->unsignedBigInteger('received_by');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Amounts (from GRN items)
            $table->decimal('total_received_amount', 15, 2)->default(0);

            // Encumbrance release
            // When GRN posted: encumbrance freed from budget_item
            //                  actual_spent on budget_item increases
            $table->boolean('encumbrance_released')->default(false);
            $table->timestamp('encumbrance_released_at')->nullable();

            // GL posting
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable(); // GL journal entry

            // Status
            $table->enum('status', [
                'draft',
                'verified',   // storekeeper verified goods
                'posted',     // GL posted, encumbrance released
                'rejected',   // goods rejected, returned to vendor
            ])->default('draft');

            $table->text('condition_notes')->nullable(); // keadaan barang
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('local_order_id')
                ->references('id')->on('local_orders');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes');
    }
};
