<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageAlertSectionItem extends Model
{
    protected $fillable = [
        'message_alert_section_id',
        'name_ar',
        'name_en',
        'sort_order',
    ];

    protected $casts = [
        'message_alert_section_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(MessageAlertSection::class, 'message_alert_section_id');
    }

    public function messageAlerts(): HasMany
    {
        return $this->hasMany(MessageAlert::class);
    }
}
