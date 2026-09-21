<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoCrawlPageIssuesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $issues = $this->issues
            ->sortBy(fn ($issue) => $this->severityRank($issue->severity))
            ->values();
        $primary = $issues->first();

        return [
            // Keep the first issue id compatible with GET /issues/{issue}.
            'id' => $primary?->id,
            'page_id' => (int) $this->id,
            'run_id' => (int) $this->seo_crawl_run_id,
            'page' => $this->path,
            'problem' => $primary
                ? (app()->getLocale() === 'en' ? $primary->message_en : $primary->message_ar)
                : null,
            'type' => $primary?->type,
            'severity' => $primary?->severity,
            'problems_count' => $issues->count(),
            'problems' => SeoCrawlIssueResource::collection($issues)->resolve($request),
        ];
    }

    private function severityRank(?string $severity): int
    {
        return match ($severity) {
            'high' => 0,
            'medium' => 1,
            'low' => 2,
            default => 3,
        };
    }
}
