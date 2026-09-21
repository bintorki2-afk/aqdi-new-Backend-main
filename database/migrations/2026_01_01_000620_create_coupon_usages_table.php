<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('contract_uuid')->nullable();
            $table->index('contract_uuid');
            $table->foreign('coupon_id', 'coupon_usages_coupon_id_foreign')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreign('user_id', 'coupon_usages_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
