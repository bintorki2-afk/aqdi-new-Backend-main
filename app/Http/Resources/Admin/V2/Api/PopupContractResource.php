<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopupContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instrument_type' => $this->instrument_type,
            'popup_status_contract' => (bool) $this->popup_status_contract,
            'popup_status_realestate' => (bool) $this->popup_status_realestate,
            'content_popup' => $this->content_popup,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
