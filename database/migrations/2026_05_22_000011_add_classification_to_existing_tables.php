<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add PTJ + Program + Activity classification to annual_budgets
        Schema::table('annual_budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('ptj_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('program_id')->nullable()->after('ptj_id');
            $table->unsignedBigInteger('activity_id')->nullable()->after('program_id');

            $table->foreign('ptj_id')->references('id')->on('ptj')->nullOnDelete();
            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('activity_id')->references('id')->on('activities')->nullOnDelete();
        });

        // Add expenditure object to budget_items
        Schema::table('budget_items', function (Blueprint $table) {
            $table->unsignedBigInteger('expenditure_object_id')->nullable()->after('id');
            $table->foreign('expenditure_object_id')->references('id')->on('expenditure_objects')->nullOnDelete();
        });

        // Add classification to journal_headers
        Schema::table('journal_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('ptj_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('program_id')->nullable()->after('ptj_id');
            $table->unsignedBigInteger('activity_id')->nullable()->after('program_id');

            $table->foreign('ptj_id')->references('id')->on('ptj')->nullOnDelete();
            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('activity_id')->references('id')->on('activities')->nullOnDelete();
        });

        // Add expenditure object + revenue code to journal_lines
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('expenditure_object_id')->nullable()->after('account_id');
            $table->unsignedBigInteger('revenue_code_id')->nullable()->after('expenditure_object_id');

            $table->foreign('expenditure_object_id')->references('id')->on('expenditure_objects')->nullOnDelete();
            $table->foreign('revenue_code_id')->references('id')->on('revenue_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['expenditure_object_id', 'revenue_code_id']);
            $table->dropColumn(['expenditure_object_id', 'revenue_code_id']);
        });

        Schema::table('journal_headers', function (Blueprint $table) {
            $table->dropForeign(['ptj_id', 'program_id', 'activity_id']);
            $table->dropColumn(['ptj_id', 'program_id', 'activity_id']);
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropForeign(['expenditure_object_id']);
            $table->dropColumn('expenditure_object_id');
        });

        Schema::table('annual_budgets', function (Blueprint $table) {
            $table->dropForeign(['ptj_id', 'program_id', 'activity_id']);
            $table->dropColumn(['ptj_id', 'program_id', 'activity_id']);
        });
    }
};
