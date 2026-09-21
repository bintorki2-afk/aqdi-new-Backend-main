<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_spend_dailies', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on');
            $table->string('platform', 32);
            $table->string('campaign_id', 64)->default('');
            $table->string('campaign_name', 191)->nullable();
            $table->string('keyword', 191)->default('');
            $table->decimal('spend', 12, 2)->default(0.00);
            $table->char('currency', 3)->default('SAR');
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedInteger('clicks')->nullable();
            $table->string('ingest_source', 16)->default('api');
            $table->timestamps();
            $table->index('platform');
            $table->unique(['spent_on', 'platform', 'campaign_id', 'keyword'], 'ad_spend_dailies_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_spend_dailies');
    }
};
