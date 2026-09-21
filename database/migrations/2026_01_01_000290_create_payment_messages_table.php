<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['success', 'failed']);
            $table->text('message')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->string('button_text_2')->nullable();
            $table->string('button_link_2')->nullable();
            $table->timestamps();
            $table->unique('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_messages');
    }
};
