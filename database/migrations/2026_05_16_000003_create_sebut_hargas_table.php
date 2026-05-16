<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sebut_hargas', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            $table->string('sh_number')->unique(); // e.g. SH-2026-00001
            $table->unsignedBigInteger('purchase_requisition_id');
            $table->date('sh_date');
            $table->string('title');
            $table->text('scope_of_work')->nullable();

            // Gov classification
            $table->unsignedBigInteger('ptj_id')->nullable();
            $table->string('objek_sebagai')->nullable();
            $table->unsignedBigInteger('budget_item_id')->nullable();

            // Quotation settings
            $table->integer('min_quotations')->default(3); // mesti dapat ≥3 sebut harga
            $table->date('closing_date');
            $table->date('evaluation_date')->nullable();

            // Amounts
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->decimal('awarded_amount', 15, 2)->nullable();

            // Awarded vendor
            $table->unsignedBigInteger('awarded_vendor_id')->nullable();
            $table->date('awarded_date')->nullable();
            $table->text('award_justification')->nullable();

            // Status
            $table->enum('status', [
                'draft',
                'issued',          // SH issued to vendors
                'closing',         // closing date passed, evaluation in progress
                'evaluated',       // evaluation done, pending approval
                'approved',        // approved, proceed to LO
                'rejected',
                'cancelled',
                'converted_lo',    // LO issued
            ])->default('draft');

            // Approval
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by');

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_requisition_id')
                ->references('id')->on('purchase_requisitions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sebut_hargas');
    }
};
