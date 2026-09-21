<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paperwork extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'contract_type',
        'icon',
    ];

    protected $appends = ['created_at_label', 'name_trans', 'icon_url'];

    public function getNameTransAttribute()
    {
        return getTransAttribute($this, 'name');
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? url("storage/{$this->icon}") : null;
    }
}
