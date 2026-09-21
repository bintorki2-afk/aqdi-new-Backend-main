<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdSpendDaily extends Model
{
    protected $table = 'ad_spend_dailies';

    protected $fillable = [
        'spent_on',
        'platform',
        'campaign_id',
        'campaign_name',
        'keyword',
        'spend',
        'currency',
        'impressions',
        'clicks',
        'ingest_source',
    ];

    protected $casts = [
        'spent_on' => 'date',
        'spend' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];
}
