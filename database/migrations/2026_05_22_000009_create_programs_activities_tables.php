<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Programs
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('ptj_id');
            $table->string('code', 20);                  // e.g. P001
            $table->string('name');                      // e.g. Program Pentadbiran Am
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('ptj_id')->references('id')->on('ptj')->onDelete('cascade');
            $table->unique(['company_id', 'ptj_id', 'code'], 'unique_program_code');
            $table->index(['company_id', 'ptj_id']);
        });

        // Activities
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('program_id');
            $table->string('code', 20);                  // e.g. A001
            $table->string('name');                      // e.g. Aktiviti Pengurusan
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->unique(['company_id', 'program_id', 'code'], 'unique_activity_code');
            $table->index(['company_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('programs');
    }
};
