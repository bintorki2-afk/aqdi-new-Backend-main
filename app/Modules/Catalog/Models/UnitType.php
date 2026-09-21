<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use App\Modules\Catalog\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;
    use HasTranslatedName;

    protected $fillable = [
        'name_ar',
        'name_en',
        'contract_type',
        'rooms',
    ];

    protected $appends = ['created_at_label', 'name_trans'];
}
