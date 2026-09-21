<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('platform', 32)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code')->nullable();
            $table->string('password')->nullable();
            $table->string('reset_password_code')->nullable();
            $table->string('google_id')->nullable();
            $table->string('fcm_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->string('theme')->nullable()->default('default');
            $table->string('theme_color')->nullable();
            $table->softDeletes();
            $table->string('utm_source', 64)->nullable();
            $table->string('utm_medium', 64)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->string('utm_term', 191)->nullable();
            $table->string('utm_content', 191)->nullable();
            $table->string('gclid', 191)->nullable();
            $table->string('fbclid', 191)->nullable();
            $table->string('ttclid', 191)->nullable();
            $table->string('twclid', 191)->nullable();
            $table->string('sccid', 191)->nullable();
            $table->timestamp('attributed_at')->nullable();
            $table->unique('email');
            $table->index('platform');
            $table->index('utm_campaign');
            $table->index('utm_source');
            $table->index('utm_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
