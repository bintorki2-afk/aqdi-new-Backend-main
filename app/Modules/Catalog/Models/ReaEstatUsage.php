<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use App\Modules\Catalog\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReaEstatUsage extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;
    use HasTranslatedName;

    protected $appends = ['created_at_label', 'name_trans'];

    protected $fillable = [
        'name_ar',
        'name_en',
        'contract_type',
    ];
}
