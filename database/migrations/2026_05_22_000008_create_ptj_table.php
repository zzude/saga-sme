<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptj', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 20)->unique();        // e.g. J001, J002
            $table->string('name');                      // e.g. Jabatan Kewangan
            $table->string('short_name')->nullable();    // e.g. KEW
            $table->text('description')->nullable();
            $table->unsignedBigInteger('head_id')->nullable(); // ketua PTJ (user)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('head_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptj');
    }
};
