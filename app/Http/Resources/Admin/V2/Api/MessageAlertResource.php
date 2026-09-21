<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->sectionItem;
        $section = $item?->section;

        return [
            'id' => $this->id,
            'type' => $section?->type,
            'message' => $this->message,
            'message_alert_section_id' => $item?->message_alert_section_id,
            'message_alert_section_item_id' => $this->message_alert_section_item_id,
            'section' => $section ? [
                'id' => $section->id,
                'name_ar' => $section->name_ar,
                'name_en' => $section->name_en,
                'type' => $section->type,
            ] : null,
            'section_item' => $item ? [
                'id' => $item->id,
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
