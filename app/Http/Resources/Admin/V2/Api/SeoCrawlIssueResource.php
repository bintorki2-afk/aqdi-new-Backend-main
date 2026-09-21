<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoCrawlIssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $message = $locale === 'en' ? $this->message_en : $this->message_ar;

        return [
            'id' => $this->id,
            'page' => $this->path,
            'problem' => $message,
            'problem_ar' => $this->message_ar,
            'problem_en' => $this->message_en,
            'type' => $this->type,
            'severity' => $this->severity,
            'details' => $this->details ?? [],
        ];
    }
}
