<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('status_type', 20)->default('contract');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('status');
            $table->string('status_label');
            $table->string('status_color')->nullable();
            $table->text('status_description')->nullable();
            $table->text('client_explanation')->nullable();
            $table->string('source', 40)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['contract_id', 'id'], 'contract_status_histories_contract_id_id_index');
            $table->index(['contract_id', 'status'], 'contract_status_histories_contract_id_status_index');
            $table->foreign('contract_id', 'contract_status_histories_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_status_histories');
    }
};
