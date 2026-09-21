<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('mobile')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
