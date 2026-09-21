<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'message',
        'button_text',
        'button_link',
        'button_text_2',
        'button_link_2',
    ];
}
