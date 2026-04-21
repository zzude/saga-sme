<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('tax_reg_no', 50)->nullable();
            $table->enum('tax_type', ['sales', 'service', 'both'])->default('service');
            $table->date('effective_date')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamps();
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_tax_profiles');
    }
};