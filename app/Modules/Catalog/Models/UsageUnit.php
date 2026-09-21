<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageUnit extends Model
{
    use HasFactory;

    protected $table = 'unit_usages';

    protected $fillable = [
        'name_ar',
        'name_en',
        'contract_type',
    ];
}
