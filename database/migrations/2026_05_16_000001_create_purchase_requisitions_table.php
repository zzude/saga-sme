<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            // Reference
            $table->string('pr_number')->unique(); // e.g. PR-2026-00001
            $table->date('pr_date');
            $table->string('title');
            $table->text('description')->nullable();

            // Gov classification (linked Kategori 3)
            $table->unsignedBigInteger('ptj_id')->nullable();
            $table->string('program_code')->nullable();
            $table->string('aktiviti_code')->nullable();
            $table->string('objek_sebagai')->nullable();  // OS code e.g. 22000
            $table->unsignedBigInteger('project_id')->nullable(); // link SPPP

            // Budget linkage (Kategori 1)
            $table->unsignedBigInteger('budget_item_id')->nullable(); // linked real-time
            $table->unsignedBigInteger('annual_budget_id')->nullable();

            // Amounts
            $table->decimal('estimated_amount', 15, 2)->default(0);

            // Procurement threshold auto-determined
            // <10k = Direct Purchase, 10k-200k = Sebut Harga, >200k = Tender
            $table->enum('procurement_method', [
                'direct_purchase',  // < RM 10,000
                'sebut_harga',      // RM 10,000 – RM 200,000
                'tender_terbuka',   // > RM 200,000 (open tender)
                'tender_terhad',    // restricted tender
                'rundingan_terus',  // direct negotiation (exception)
            ])->default('direct_purchase');

            // Workflow status
            $table->enum('status', [
                'draft',
                'submitted',         // submitted for HOD approval
                'approved_hod',      // Head of Department approved
                'approved_finance',  // Finance approved
                'rejected',
                'cancelled',
                'converted_lo',      // converted to Local Order
                'converted_sh',      // converted to Sebut Harga
                'converted_tender',  // converted to Tender
            ])->default('draft');

            // Requestor
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by_hod')->nullable();
            $table->timestamp('approved_hod_at')->nullable();
            $table->unsignedBigInteger('approved_by_finance')->nullable();
            $table->timestamp('approved_finance_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
