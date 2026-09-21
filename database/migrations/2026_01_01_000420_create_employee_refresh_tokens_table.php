<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('token_hash', 64);
            $table->boolean('remembered')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'revoked_at'], 'employee_refresh_tokens_employee_id_revoked_at_index');
            $table->index('expires_at');
            $table->unique('token_hash');
            $table->foreign('employee_id', 'employee_refresh_tokens_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_refresh_tokens');
    }
};
