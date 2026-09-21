<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('mobile');
            $table->string('password');
            $table->string('email');
            $table->boolean('is_seo')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code')->nullable();
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seos');
    }
};
