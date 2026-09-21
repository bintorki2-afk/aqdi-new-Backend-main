<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_histories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('login_at');
            $table->dateTime('logout_at')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id', 'auth_histories_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('user_id', 'auth_histories_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_histories');
    }
};
