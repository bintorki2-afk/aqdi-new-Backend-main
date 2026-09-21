<?php

namespace App\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => $this->message,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'button_text_2' => $this->button_text_2,
            'button_link_2' => $this->button_link_2,
        ];
    }
}
