<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overviews', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name_overview');
            $table->string('value');
            $table->text('image');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overviews');
    }
};
