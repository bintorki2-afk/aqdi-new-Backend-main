<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('code_coupon');
            $table->enum('type_coupon', ['ratio', 'value'])->default('ratio');
            $table->string('value_coupon');
            $table->date('date_start');
            $table->date('date_end');
            $table->integer('usage');
            $table->integer('usage_of_user');
            $table->boolean('is_review')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->unique('code_coupon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
