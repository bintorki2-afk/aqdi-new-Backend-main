<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

trait MergesMessageAlertRequestAliases
{
    protected function mergeMessageAlertSectionAliases(Request $request): void
    {
        $this->applyMessageAlertAliasMap($request, [
            'nameAr' => 'name_ar',
            'title_ar' => 'name_ar',
            'titleAr' => 'name_ar',
            'nameEn' => 'name_en',
            'title_en' => 'name_en',
            'titleEn' => 'name_en',
            'sortOrder' => 'sort_order',
        ]);
    }

    protected function mergeMessageAlertAliases(Request $request): void
    {
        $this->applyMessageAlertAliasMap($request, [
            'messageAlertSectionId' => 'message_alert_section_id',
            'section_id' => 'message_alert_section_id',
            'sectionId' => 'message_alert_section_id',
            'messageAlertSectionItemId' => 'message_alert_section_item_id',
            'section_item_id' => 'message_alert_section_item_id',
            'sectionItemId' => 'message_alert_section_item_id',
            'item_id' => 'message_alert_section_item_id',
            'text' => 'message',
            'message_text' => 'message',
        ]);
    }

    protected function mergeMessageAlertSectionItemAliases(Request $request): void
    {
        $this->applyMessageAlertAliasMap($request, [
            'messageAlertSectionId' => 'message_alert_section_id',
            'section_id' => 'message_alert_section_id',
            'sectionId' => 'message_alert_section_id',
            'nameAr' => 'name_ar',
            'title_ar' => 'name_ar',
            'titleAr' => 'name_ar',
            'nameEn' => 'name_en',
            'title_en' => 'name_en',
            'titleEn' => 'name_en',
            'sortOrder' => 'sort_order',
        ]);
    }

    /**
     * Copy alternate JSON keys into snake_case keys expected by validation.
     */
    private function applyMessageAlertAliasMap(Request $request, array $map): void
    {
        foreach ($map as $from => $to) {
            if (! $request->exists($from)) {
                continue;
            }

            $targetMissingOrEmpty = ! $request->exists($to)
                || $request->input($to) === null
                || $request->input($to) === '';

            if ($targetMissingOrEmpty) {
                $request->merge([$to => $request->input($from)]);
            }
        }
    }
}
