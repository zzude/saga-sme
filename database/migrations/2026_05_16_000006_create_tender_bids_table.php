<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_bids', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();
            $table->unsignedBigInteger('tender_id');

            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_name');
            $table->string('vendor_registration_no')->nullable();

            $table->date('bid_date');
            $table->string('bid_reference')->nullable();

            // Bid amounts
            $table->decimal('bid_amount', 15, 2);
            $table->integer('completion_days')->nullable(); // tempoh siapkan kerja

            // Compliance
            $table->boolean('bumiputera_status')->default(false);
            $table->string('cidb_grade')->nullable();    // for construction
            $table->string('license_class')->nullable(); // for services

            // Technical evaluation (Penilaian Teknikal)
            $table->decimal('technical_score', 5, 2)->nullable();
            $table->decimal('financial_score', 5, 2)->nullable();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->text('evaluation_remarks')->nullable();

            $table->boolean('is_compliant')->default(true);   // lulus syarat kelayakan
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_awarded')->default(false);

            $table->string('bid_document_path')->nullable();

            $table->timestamps();

            $table->foreign('tender_id')
                ->references('id')->on('tenders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_bids');
    }
};
