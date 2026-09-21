<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refundable_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->boolean('has_draft_contract')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('admin_confirmed')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->timestamps();
            $table->foreign('contract_id', 'refundable_contracts_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('employee_id', 'refundable_contracts_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('user_id', 'refundable_contracts_user_id_foreign')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refundable_contracts');
    }
};
