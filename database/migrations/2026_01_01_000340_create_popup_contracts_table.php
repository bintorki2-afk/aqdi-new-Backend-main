<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_contracts', function (Blueprint $table) {
            $table->id();
            $table->enum('instrument_type', ['electronic', 'old_handwritten', 'strong_argument', 'electronic_tax_register', 'property_ownership_owner_are_deceased_endowment', 'property_ownership_owner_is_endowment', 'sale_agreement', 'electronic_deed_from_the_ministry_of_justice', 'economic_cities_authority_suspended', 'sublease_agreement', 'lease_renewal', 'property_ownership_owner_are_suspended', 'property_ownership_owner_are_deceased']);
            $table->boolean('popup_status_contract')->default(false);
            $table->boolean('popup_status_realestate')->default(false);
            $table->text('content_popup')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_contracts');
    }
};
