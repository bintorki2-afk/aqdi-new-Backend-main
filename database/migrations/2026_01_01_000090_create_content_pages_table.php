<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key');
            $table->json('content_json')->nullable();
            $table->timestamps();
            $table->unique('page_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
