<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->enum('work_period', ['morning', 'evening'])->default('morning');
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('facebook')->nullable();
            $table->string('role')->nullable();
            $table->string('instagram')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('snapchat')->nullable();
            $table->string('tiktok')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_online')->default(false);
            $table->text('reason_of_block')->nullable();
            $table->date('blocked_until')->nullable();
            $table->string('x')->nullable();
            $table->string('password');
            $table->text('fcm_token')->nullable();
            $table->timestamps();
            $table->unique('email');
            $table->unique('phone');
            $table->foreign('role_id', 'employees_role_id_foreign')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
