<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page' => $this->page,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'description' => $this->description_trans,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
