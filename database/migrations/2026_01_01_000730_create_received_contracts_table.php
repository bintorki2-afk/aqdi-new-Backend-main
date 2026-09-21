<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('received_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('employee_id');
            $table->enum('status', ['pending', 'finish'])->default('pending');
            $table->text('notes')->nullable();
            $table->date('date_of_received')->nullable();
            $table->timestamps();
            $table->unique('contract_id');
            $table->foreign('contract_id', 'received_contracts_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('employee_id', 'received_contracts_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_contracts');
    }
};
