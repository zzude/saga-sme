<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            // Reference
            $table->string('quotation_number')->unique(); // QT-2026-00001
            $table->integer('revision')->default(0);      // Rev 0 = original, Rev 1, Rev 2...
            $table->string('quotation_ref')->index();     // QT-2026-00001 (same across revisions)

            $table->date('quotation_date');
            $table->date('valid_until');                  // expiry date
            $table->string('title')->nullable();          // e.g. "Supply of IT Equipment"

            // Customer
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_name');              // denormalized for PDF
            $table->text('customer_address')->nullable();
            $table->string('attention_to')->nullable();   // Attn: ...

            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0); // total from line discounts
            $table->decimal('taxable_amount', 15, 2)->default(0);  // after discount
            $table->decimal('sst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            // SST
            $table->boolean('sst_applicable')->default(false);
            $table->decimal('sst_rate', 5, 2)->default(6.00);

            // Terms
            $table->integer('payment_terms_days')->default(30);
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();            // internal notes
            $table->text('remarks')->nullable();          // shown on PDF

            // Status
            $table->enum('status', [
                'draft',
                'sent',       // sent to customer
                'accepted',   // customer accepted
                'rejected',   // customer rejected
                'expired',    // valid_until passed
                'cancelled',
                'converted',  // converted to invoice
            ])->default('draft');

            // Conversion tracking
            $table->unsignedBigInteger('converted_invoice_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('converted_by')->nullable();

            // Revision tracking
            $table->unsignedBigInteger('parent_quotation_id')->nullable(); // previous revision
            $table->boolean('is_latest_revision')->default(true);

            // Audit
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable(); // internal user who marks accepted
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
