<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_seo_connections', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('google');
            $table->string('google_email')->nullable();
            $table->string('google_user_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('search_console_site_url', 512)->nullable();
            $table->string('analytics_property_id', 64)->nullable();
            $table->unsignedBigInteger('connected_by_employee_id')->nullable();
            $table->timestamps();
            $table->unique('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_seo_connections');
    }
};
