<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use App\Modules\Catalog\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;
    use HasTranslatedName;

    protected $table = 'cities';

    protected $fillable = [
        'region_id',
        'name_ar',
        'name_en',
    ];

    protected $appends = ['created_at_label', 'name_trans'];

    public function regions(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
