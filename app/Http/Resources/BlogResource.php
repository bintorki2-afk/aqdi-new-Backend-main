<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       
        return [
        'id'=>$this->id,
        'title'=>$this->title,
        'description'=>$this->description,
        'slug'=>$this->slug,
        'author'=> $this->whenLoaded('admins', fn () => $this->admins->name),
        'image' => $this->image ? url("storage/{$this->image}") : null,
        'imageAlt' => $this->image_alt ?: $this->title,
        'status'=>$this->status,
        'timePublish'=>$this->publish_at,
        'isActive'=>$this->is_active,
        'metaTitle'=>$this->meta_title,
        'metaDescription'=>$this->meta_description,
 
        ];
    }
   
}
