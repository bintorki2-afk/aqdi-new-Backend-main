<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_roles', function (Blueprint $table) {
            $table->id();
            $table->string('text_of_reason');
            $table->text('service_definition')->nullable();
            $table->string('input_field_label')->nullable();
            $table->string('input_field_type', 20)->nullable();
            $table->string('icon')->nullable();
            $table->string('input_icon')->nullable();
            $table->boolean('pop')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_roles');
    }
};
