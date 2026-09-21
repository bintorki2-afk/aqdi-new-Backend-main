<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructionSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title_ar' => $this->title_ar,
            'description_ar' => $this->description_ar,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'images' => InstructionSectionImageResource::collection($this->images),
        ];
    }
}
