<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageAlertSection extends Model
{
    public const TYPE_CLIENT = 'client';

    public const TYPE_EMPLOYEE = 'employee';

    protected $fillable = [
        'name_ar',
        'name_en',
        'sort_order',
        'type',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MessageAlertSectionItem::class);
    }
}
