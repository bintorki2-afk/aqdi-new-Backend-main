<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->string('color_text')->nullable();
            $table->text('description')->nullable();
            $table->text('client_explanation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_statuses');
    }
};
