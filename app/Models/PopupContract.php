<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'instrument_type',
        'popup_status_contract',
        'popup_status_realestate',
        'content_popup',
        'button_text',
        'button_link',
    ];

    protected $casts = [
        'popup_status_contract' => 'boolean',
        'popup_status_realestate' => 'boolean',
    ];
}
