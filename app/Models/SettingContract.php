<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'instrument_type',
        'realestate',
        'contract',
        'label',
    ];

    protected $casts = [
        'realestate' => 'boolean',
        'contract' => 'boolean',
    ];
}
