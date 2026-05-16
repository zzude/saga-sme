<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            $table->string('tender_number')->unique(); // e.g. KPD-T-2026-001
            $table->unsignedBigInteger('purchase_requisition_id');
            $table->date('tender_date');
            $table->string('title');
            $table->text('scope_of_work')->nullable();

            // Gov classification
            $table->unsignedBigInteger('ptj_id')->nullable();
            $table->string('objek_sebagai')->nullable();
            $table->unsignedBigInteger('budget_item_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Tender type
            $table->enum('tender_type', [
                'terbuka',      // open tender
                'terhad',       // restricted tender
                'rundingan',    // direct negotiation
            ])->default('terbuka');

            // Timeline
            $table->date('advertisement_date')->nullable();  // tarikh iklan
            $table->date('document_sale_start')->nullable(); // jual dokumen mula
            $table->date('document_sale_end')->nullable();   // jual dokumen tamat
            $table->date('site_visit_date')->nullable();
            $table->date('closing_date');
            $table->date('opening_date')->nullable();        // tender opening ceremony
            $table->date('evaluation_date')->nullable();
            $table->date('award_date')->nullable();

            // Pricing
            $table->decimal('document_price', 10, 2)->default(0); // harga dokumen tender
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->decimal('awarded_amount', 15, 2)->nullable();

            // Award
            $table->unsignedBigInteger('awarded_vendor_id')->nullable();
            $table->text('award_justification')->nullable();

            // Tender committee (JKPT)
            $table->json('committee_members')->nullable(); // array of user_ids

            // Status
            $table->enum('status', [
                'draft',
                'advertised',       // iklan diterbitkan
                'open',             // menerima tawaran
                'closed',           // closing date passed
                'evaluating',       // penilaian dalam proses
                'recommended',      // JKPT dah buat syor
                'approved',         // KSU/LembagaTender lulus
                'rejected',
                'cancelled',
                'awarded',          // awarded to vendor
                'converted_contract', // contract signed
            ])->default('draft');

            // Approval chain (more formal than SH)
            $table->unsignedBigInteger('recommended_by')->nullable();
            $table->timestamp('recommended_at')->nullable();
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
        Schema::dropIfExists('tenders');
    }
};
