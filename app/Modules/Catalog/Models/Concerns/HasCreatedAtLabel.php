<?php

namespace App\Modules\Catalog\Models\Concerns;

trait HasCreatedAtLabel
{
    public function getCreatedAtLabelAttribute(): string
    {
        return date('Y-m-d H:i A', strtotime((string) $this->created_at));
    }
}
