<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use App\Modules\Catalog\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReaEstatType extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;
    use HasTranslatedName;

    protected $appends = ['created_at_label'];

    protected $fillable = [
        'contract_type',
        'name_ar',
        'name_en',
    ];
}
