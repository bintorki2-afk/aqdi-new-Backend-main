<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('addition_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->decimal('deduction', 12, 2)->default(0.00);
            $table->decimal('bonus', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->nullable();
            $table->string('month');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->foreign('employee_id', 'salaries_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
