<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('owner_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')
                ->default('draft')
                ->after('is_active')
                ->comment('draft|active|suspended');

            $table->unsignedTinyInteger('onboarding_step')
                ->default(1)
                ->after('status');

            $table->timestamp('onboarding_completed_at')
                ->nullable()
                ->after('onboarding_step');

            $table->index('status');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['owner_id']);
            $table->dropColumn([
                'owner_id',
                'status',
                'onboarding_step',
                'onboarding_completed_at',
            ]);
        });
    }
};
