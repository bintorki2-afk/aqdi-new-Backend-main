<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use App\Modules\Catalog\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;
    use HasTranslatedName;

    protected $table = 'regions';

    protected $fillable = [
        'name_ar',
        'name_en',
    ];

    protected $appends = ['created_at_label', 'name_trans'];

    public function city(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function getCityNameAttribute()
    {
        return $this->city->name_ar;
    }
}
