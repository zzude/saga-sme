<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billplz_bills', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('billplz_id')->unique();           // Bill ID from Billplz
            $table->string('collection_id');
            $table->string('billable_type')->nullable();      // Invoice, CompanyPlan
            $table->unsignedBigInteger('billable_id')->nullable();
            $table->string('reference_no')->nullable();       // our internal ref
            $table->string('description');
            $table->decimal('amount', 10, 2);                 // in MYR
            $table->string('payer_name')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('payer_phone')->nullable();
            $table->string('status')->default('pending');     // pending, paid, failed
            $table->string('url')->nullable();                // payment URL
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_amount')->nullable();
            $table->string('transaction_id')->nullable();     // Billplz transaction ID
            $table->string('transaction_status')->nullable(); // success, failed
            $table->json('callback_data')->nullable();        // raw callback payload
            $table->timestamps();

            $table->index(['billable_type', 'billable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billplz_bills');
    }
};
