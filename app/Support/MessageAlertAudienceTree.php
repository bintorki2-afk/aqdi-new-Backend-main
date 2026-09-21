<?php

namespace App\Support;

use App\Http\Resources\Admin\V2\Api\MessageAlertResource;
use App\Models\MessageAlertSection;
use Illuminate\Support\Collection;

final class MessageAlertAudienceTree
{
    /**
     * @param  Collection<int, MessageAlertSection>  $sections
     * @return array<string, mixed>
     */
    public static function build(string $type, Collection $sections): array
    {
        $messagesCount = 0;

        $sectionsPayload = $sections->map(function (MessageAlertSection $section) use (&$messagesCount) {
            $items = $section->items->map(function ($item) use (&$messagesCount) {
                $messages = MessageAlertResource::collection($item->messageAlerts)->resolve();
                $count = count($messages);
                $messagesCount += $count;

                return [
                    'id' => $item->id,
                    'message_alert_section_id' => $item->message_alert_section_id,
                    'name_ar' => $item->name_ar,
                    'name_en' => $item->name_en,
                    'sort_order' => $item->sort_order,
                    'messages' => $messages,
                    'messages_count' => $count,
                ];
            })->values();

            return [
                'id' => $section->id,
                'name_ar' => $section->name_ar,
                'name_en' => $section->name_en,
                'type' => $section->type,
                'sort_order' => $section->sort_order,
                'items' => $items,
                'messages_count' => $items->sum('messages_count'),
            ];
        })->values();

        return [
            'type' => $type,
            'type_label_ar' => MessageAlertType::labelAr($type),
            'type_label_en' => MessageAlertType::labelEn($type),
            'sections_count' => $sections->count(),
            'messages_count' => $messagesCount,
            'sections' => $sectionsPayload,
        ];
    }
}
