<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoCrawlPageDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'url' => $this->url,
            'path' => $this->path,
            'status_code' => (int) $this->status_code,
            'load_time_ms' => (int) $this->load_time_ms,
            'content_type' => $this->content_type,
            'title' => $this->title,
            'meta_description' => $this->meta_description,
            'h1_count' => (int) $this->h1_count,
            'image_count' => (int) $this->image_count,
            'images_missing_alt' => (int) $this->images_missing_alt,
            'outbound_internal_links' => (int) $this->outbound_internal_links,
            'inbound_internal_links' => (int) $this->inbound_internal_links,
            'is_html' => (bool) $this->is_html,
            'is_indexable' => (bool) $this->is_indexable,
            'is_healthy' => (bool) $this->is_healthy,
        ];
    }
}
