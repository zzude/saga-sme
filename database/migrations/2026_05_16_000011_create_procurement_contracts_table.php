<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();

            $table->string('contract_number')->unique(); // e.g. KPD-K-2026-001
            $table->unsignedBigInteger('tender_id')->nullable();
            $table->date('contract_date');
            $table->string('title');
            $table->text('scope_of_work')->nullable();

            // Vendor
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_name');

            // Gov classification
            $table->unsignedBigInteger('ptj_id')->nullable();
            $table->string('objek_sebagai')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('budget_item_id')->nullable();

            // Contract type
            $table->enum('contract_type', [
                'supply',           // bekalan
                'services',         // perkhidmatan
                'works',            // kerja (construction)
                'consultancy',      // perundingan
                'maintenance',      // penyelenggaraan
            ])->default('supply');

            // Contract value & duration
            $table->decimal('contract_amount', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_months')->storedAs('TIMESTAMPDIFF(MONTH, start_date, end_date)');

            // Performance bond / Securiy deposit
            $table->boolean('performance_bond_required')->default(false);
            $table->decimal('performance_bond_amount', 15, 2)->default(0);
            $table->string('performance_bond_reference')->nullable();
            $table->date('performance_bond_expiry')->nullable();

            // Progress claims tracking
            $table->decimal('total_claimed', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('retention_amount', 15, 2)->default(0);    // wang tahanan
            $table->decimal('retention_released', 15, 2)->default(0);

            // Extension / variation
            $table->boolean('has_variation_order')->default(false);
            $table->decimal('variation_amount', 15, 2)->default(0);     // VO amount
            $table->decimal('revised_contract_amount', 15, 2)
                ->storedAs('contract_amount + variation_amount');

            // Status
            $table->enum('status', [
                'draft',
                'active',           // contract in force
                'extended',         // lanjutan tempoh
                'completed',        // fully delivered & paid
                'terminated',       // terminated early
                'expired',          // end date passed
            ])->default('draft');

            $table->date('completion_date_actual')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->date('signed_date')->nullable();
            $table->string('contract_document_path')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tender_id')
                ->references('id')->on('tenders');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_contracts');
    }
};
