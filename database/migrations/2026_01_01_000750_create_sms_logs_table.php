<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phone_number');
            $table->text('message');
            $table->string('sms_id')->nullable();
            $table->string('type');
            $table->decimal('cost', 10, 4)->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
            $table->foreign('user_id', 'sms_logs_user_id_foreign')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
