<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Client/mobile payload for instructional images (no admin-only fields).
 */
class InstructionSectionImageClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'image_url' => $this->image_url,
            'file_extension' => $this->file_extension,
            'sort_order' => $this->sort_order,
        ];
    }
}
