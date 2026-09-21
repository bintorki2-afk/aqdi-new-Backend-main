<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Support\MessageAlertType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MessageAlertCreateFormResource extends JsonResource
{
    /**
     * @param  Collection<int, \App\Models\MessageAlertSection>  $sections
     */
    public static function forAudience(string $type, Collection $sections): self
    {
        return new self([
            'type' => $type,
            'sections' => $sections,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->resource['type'];
        $sections = $this->resource['sections'];

        $firstSection = $sections->first();
        $firstItem = $firstSection?->items->first();

        return [
            'type' => $type,
            'type_label_ar' => MessageAlertType::labelAr($type),
            'type_label_en' => MessageAlertType::labelEn($type),
            'sections' => MessageAlertSectionWithItemsResource::collection($sections),
            'example_body' => [
                'message_alert_section_id' => $firstSection?->id,
                'message_alert_section_item_id' => $firstItem?->id,
                'message' => '',
            ],
        ];
    }
}
