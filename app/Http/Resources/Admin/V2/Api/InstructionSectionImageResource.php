<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructionSectionImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->instruction_section_id,
            'title_ar' => $this->title_ar,
            'image_url' => $this->image_url,
            'mime_type' => $this->mime_type,
            'file_extension' => $this->file_extension,
            'sort_order' => $this->sort_order,
        ];
    }
}
