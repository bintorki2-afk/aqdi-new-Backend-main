<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructionSectionImage extends Model
{
    protected $fillable = [
        'instruction_section_id',
        'title_ar',
        'path',
        'mime_type',
        'file_extension',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(InstructionSection::class, 'instruction_section_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->path ? getFilePath($this->path) : null;
    }
}
