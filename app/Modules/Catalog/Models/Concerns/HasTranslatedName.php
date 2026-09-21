<?php

namespace App\Modules\Catalog\Models\Concerns;

trait HasTranslatedName
{
    public function getNameTransAttribute()
    {
        return getTransAttribute($this, 'name');
    }
}
