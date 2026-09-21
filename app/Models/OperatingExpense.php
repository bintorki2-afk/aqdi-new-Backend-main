<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperatingExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expense',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
