<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500);
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamps();
            $table->index('ip_address');
            $table->index(['user_id', 'session_id'], 'admin_sessions_user_id_session_id_index');
            $table->foreign('user_id', 'admin_sessions_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_sessions');
    }
};
