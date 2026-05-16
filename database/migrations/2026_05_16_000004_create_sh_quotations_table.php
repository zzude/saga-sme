<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sh_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->index();
            $table->unsignedBigInteger('sebut_harga_id');

            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_name'); // denormalized for report
            $table->string('vendor_registration_no')->nullable(); // SSM/ROB

            $table->date('quotation_date');
            $table->string('quotation_reference')->nullable();
            $table->decimal('quoted_amount', 15, 2);
            $table->integer('delivery_days')->nullable(); // delivery period offered

            // Evaluation scoring (Penilaian Sebut Harga)
            $table->decimal('technical_score', 5, 2)->nullable();  // 0-100
            $table->decimal('financial_score', 5, 2)->nullable();  // 0-100
            $table->decimal('total_score', 5, 2)->nullable();

            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_awarded')->default(false);
            $table->text('evaluation_notes')->nullable();

            // Document attachment reference
            $table->string('quotation_document_path')->nullable();

            $table->timestamps();

            $table->foreign('sebut_harga_id')
                ->references('id')->on('sebut_hargas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sh_quotations');
    }
};
