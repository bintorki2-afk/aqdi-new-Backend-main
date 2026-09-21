<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('employee_id');
            $table->longText('comment');
            $table->timestamps();
            $table->foreign('contract_id', 'contract_comments_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('employee_id', 'contract_comments_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_comments');
    }
};
