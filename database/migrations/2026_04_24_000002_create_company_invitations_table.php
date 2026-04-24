<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('invited_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('email');
            $table->string('role')->default('user');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            // Satu email boleh ada satu pending invite per company je
            $table->unique(['company_id', 'email']);
            $table->index('token');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invitations');
    }
};
