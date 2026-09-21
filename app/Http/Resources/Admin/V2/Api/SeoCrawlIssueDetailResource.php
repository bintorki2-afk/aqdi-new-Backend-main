<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;

class SeoCrawlIssueDetailResource extends SeoCrawlIssueResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $issue = parent::toArray($request);
        $page = $this->resource->relationLoaded('page') ? $this->page : null;
        $issues = $page && $page->relationLoaded('issues')
            ? $page->issues
            : collect([$this->resource]);

        return [
            ...$issue,
            'run_id' => (int) $this->seo_crawl_run_id,
            'problems_count' => $issues->count(),
            'problems' => SeoCrawlIssueResource::collection($issues)->resolve($request),
            'page_details' => $page
                ? (new SeoCrawlPageDetailResource($page))->resolve($request)
                : null,
        ];
    }
}
