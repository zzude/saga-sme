<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_orders', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            $table->string('lo_number')->unique(); // e.g. LO-2026-00001
            $table->date('lo_date');
            $table->string('title');

            // Source document (one of these will be set)
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->unsignedBigInteger('sebut_harga_id')->nullable();
            $table->unsignedBigInteger('tender_id')->nullable();

            // Vendor
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_name'); // denormalized

            // Gov classification
            $table->unsignedBigInteger('ptj_id')->nullable();
            $table->string('program_code')->nullable();
            $table->string('aktiviti_code')->nullable();
            $table->string('objek_sebagai')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Budget linkage — ENCUMBRANCE
            $table->unsignedBigInteger('budget_item_id'); // REQUIRED — commit peruntukan
            $table->unsignedBigInteger('annual_budget_id');
            $table->unsignedBigInteger('warrant_item_id')->nullable();

            // Amounts
            $table->decimal('lo_amount', 15, 2);         // total LO amount
            $table->decimal('received_amount', 15, 2)->default(0);  // dari GRN
            $table->decimal('invoiced_amount', 15, 2)->default(0);  // dari vendor invoice
            $table->decimal('paid_amount', 15, 2)->default(0);      // bayaran dibuat

            // Encumbrance tracking (real-time)
            // When LO posted: budget_items.encumbered_amount += lo_amount
            // When GRN posted: encumbrance freed, actual_spent increases
            $table->boolean('encumbrance_posted')->default(false);
            $table->timestamp('encumbrance_posted_at')->nullable();
            $table->boolean('encumbrance_released')->default(false);
            $table->timestamp('encumbrance_released_at')->nullable();

            // Delivery
            $table->date('delivery_date_required')->nullable();
            $table->date('delivery_date_actual')->nullable();
            $table->text('delivery_address')->nullable();

            // Terms
            $table->integer('payment_terms_days')->default(30);
            $table->text('terms_conditions')->nullable();

            // Status
            $table->enum('status', [
                'draft',
                'approved',         // LO approved, encumbrance committed
                'issued',           // LO sent to vendor
                'partial_received', // partial GRN done
                'fully_received',   // all items received (GRN complete)
                'invoiced',         // vendor invoice received
                'paid',             // payment done
                'closed',           // LO closed
                'cancelled',        // LO cancelled, encumbrance released
            ])->default('draft');

            // Approval
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by');

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_orders');
    }
};
