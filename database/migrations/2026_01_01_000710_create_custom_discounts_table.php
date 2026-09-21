<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contract_id');
            $table->string('contract_uuid')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->unsignedBigInteger('coupon_usage_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('type', 20);
            $table->decimal('value', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_before', 10, 2)->default(0.00);
            $table->decimal('total_after', 10, 2)->default(0.00);
            $table->text('reason');
            $table->timestamps();
            $table->index('contract_uuid');
            $table->index('employee_id');
            $table->foreign('contract_id', 'custom_discounts_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('coupon_id', 'custom_discounts_coupon_id_foreign')->references('id')->on('coupons')->nullOnDelete();
            $table->foreign('coupon_usage_id', 'custom_discounts_coupon_usage_id_foreign')->references('id')->on('coupon_usages')->nullOnDelete();
            $table->foreign('user_id', 'custom_discounts_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_discounts');
    }
};
