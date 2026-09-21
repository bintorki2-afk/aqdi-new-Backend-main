<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CR2 "العملاء المحتملون" (potential customers): a lead is captured when a visitor
 * reaches the payment screen but does not pay. It converts to "paid" if they pay later.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            return;
        }

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('contract_uuid')->nullable()->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('contract_type')->nullable();
            // potential = reached payment screen, not paid; paid = later completed payment.
            $table->string('status')->default('potential')->index();
            $table->string('source')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
