<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'subscribed_at',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];
}
