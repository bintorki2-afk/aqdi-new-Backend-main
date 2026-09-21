<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('twitter')->nullable();
            $table->string('snapchat')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('linkedIn')->nullable();
            $table->string('whatsapp_contact')->nullable();
            $table->string('whatsapp_contract')->nullable();
            $table->float('housing_tax', 8, 2)->nullable();
            $table->float('commercial_tax', 8, 2)->nullable();
            $table->float('application_fees', 8, 2)->nullable();
            $table->boolean('open_payment')->default(true);
            $table->boolean('is_open')->default(true);
            $table->string('working_hours')->nullable();
            $table->string('version')->nullable();
            $table->unsignedInteger('time_to_documentation_contract')->nullable();
            $table->longText('text_message_user')->nullable();
            $table->longText('text_message_admin')->nullable();
            $table->longText('sms_user')->nullable();
            $table->longText('sms_owner')->nullable();
            $table->longText('sms_employee')->nullable();
            $table->decimal('electricity_meter_fee_commercial_tenant', 12, 2)->nullable();
            $table->timestamps();
            $table->string('cover')->nullable();
            $table->string('banner')->nullable();
            $table->decimal('electricity_meter_fee_housing_tenant', 12, 2)->nullable();
            $table->decimal('water_meter_fee_commercial_tenant', 12, 2)->nullable();
            $table->decimal('water_meter_fee_housing_tenant', 12, 2)->nullable();
            $table->decimal('moyasar_fee_percent', 5, 2)->nullable();
            $table->decimal('monthly_salaries', 12, 2)->nullable();
            $table->decimal('operating_budget', 12, 2)->nullable();
            $table->decimal('marketing_budget', 12, 2)->nullable();
            $table->decimal('moyasar_mada_percent', 5, 2)->nullable();
            $table->decimal('moyasar_credit_percent', 5, 2)->nullable();
            $table->decimal('moyasar_fixed_fee', 8, 2)->nullable();
            $table->decimal('meter_transfer_fee', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
