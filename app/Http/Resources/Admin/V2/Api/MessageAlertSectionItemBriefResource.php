<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAlertSectionItemBriefResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_alert_section_id' => $this->message_alert_section_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'sort_order' => $this->sort_order,
        ];
    }
}
