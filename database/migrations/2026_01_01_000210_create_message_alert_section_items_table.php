<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_alert_section_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_alert_section_id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('message_alert_section_id', 'message_alert_section_items_message_alert_section_id_foreign')->references('id')->on('message_alert_sections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_alert_section_items');
    }
};
