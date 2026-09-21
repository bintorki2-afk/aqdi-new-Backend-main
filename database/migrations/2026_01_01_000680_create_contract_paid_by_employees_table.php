<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_paid_by_employees', function (Blueprint $table) {
            $table->id();
            $table->string('contract_uuid')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->string('customer_mobile', 32);
            $table->enum('contract_type', ['housing', 'commercial'])->nullable();
            $table->unsignedBigInteger('contract_period_id')->nullable();
            $table->string('draft_contract_number', 32)->nullable();
            $table->unsignedBigInteger('draft_contract_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('contract_uuid');
            $table->index(['employee_id', 'is_paid'], 'contract_paid_by_employees_employee_id_is_paid_index');
            $table->foreign('contract_period_id', 'contract_paid_by_employees_contract_period_id_foreign')->references('id')->on('contract_periods')->nullOnDelete();
            $table->foreign('draft_contract_id', 'contract_paid_by_employees_draft_contract_id_foreign')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('employee_id', 'contract_paid_by_employees_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_paid_by_employees');
    }
};
