<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_alert_section_item_id');
            $table->text('message');
            $table->timestamps();
            $table->foreign('message_alert_section_item_id', 'message_alerts_message_alert_section_item_id_foreign')->references('id')->on('message_alert_section_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_alerts');
    }
};
