<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteOrder extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_DONE = 'done';

    protected $fillable = [
        'order_number',
        'contract_type',
        'whatsapp_number',
        'sections',
        'notes',
        'source',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'sections' => 'array',
        'submitted_at' => 'datetime',
    ];
}
