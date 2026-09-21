<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAlert extends Model
{
    protected $fillable = [
        'message_alert_section_item_id',
        'message',
    ];

    protected $casts = [
        'message_alert_section_item_id' => 'integer',
    ];

    public function sectionItem(): BelongsTo
    {
        return $this->belongsTo(MessageAlertSectionItem::class, 'message_alert_section_item_id');
    }
}
