<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sequence');
            $table->string('invoice_number');
            $table->string('order_number')->nullable();
            $table->date('date');
            $table->string('customer_phone')->nullable();
            $table->text('description')->nullable();
            $table->decimal('rental_fees', 10, 2)->default(0.00);
            $table->decimal('service_fees', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('created_by_employee_id')->nullable();
            $table->timestamps();
            $table->index(['contract_id', 'created_by_employee_id'], 'invoices_contract_id_created_by_employee_id_index');
            $table->unique('invoice_number');
            $table->unique('sequence');
            $table->foreign('contract_id', 'invoices_contract_id_foreign')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('created_by_employee_id', 'invoices_created_by_employee_id_foreign')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
