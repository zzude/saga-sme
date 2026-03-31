<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable()->comment('Income Tax Number');
            $table->string('sst_number')->nullable()->comment('SST Registration Number');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('country', 2)->default('MY');
            $table->string('currency', 3)->default('MYR');
            $table->string('timezone')->default('Asia/Kuala_Lumpur');
            $table->date('financial_year_start')->nullable()->comment('Day/month the financial year begins');
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
