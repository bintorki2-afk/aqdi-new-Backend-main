<?php

namespace App\Modules\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePricingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name_trans,
            'price' => $this->price,
        ];
    }
}
