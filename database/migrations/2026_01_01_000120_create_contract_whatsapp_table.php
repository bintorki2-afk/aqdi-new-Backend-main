<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_number');
            $table->dateTime('addition_date');
            $table->enum('contract_type', ['commercial', 'residential'])->nullable();
            $table->boolean('without')->default(false);
            $table->boolean('derived_from_bank')->default(false);
            $table->boolean('waqf')->default(false);
            $table->boolean('paper_deed')->default(false);
            $table->boolean('paper_deed_2')->default(false);
            $table->boolean('is_documented')->nullable();
            $table->unsignedBigInteger('contract_duration')->nullable();
            $table->decimal('amount_paid_by_client', 10, 2)->nullable();
            $table->decimal('rental_fees', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('time')->nullable();
            $table->date('date')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->timestamps();
            $table->foreign('contract_duration', 'contract_whatsapp_contract_duration_foreign')->references('id')->on('contract_periods')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_whatsapp');
    }
};
