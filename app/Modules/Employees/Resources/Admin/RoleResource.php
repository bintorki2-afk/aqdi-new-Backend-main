<?php

namespace App\Modules\Employees\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employees = $this->whenLoaded('employees', fn () => $this->employees);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'title' => $this->title_trans ?? $this->title_ar,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_full_access' => $this->isFullAccess(),
            'permissions_count' => $this->permissions_count
                ?? ($this->relationLoaded('permissions') ? $this->permissions->count() : $this->permissions()->count()),
            'employee_names' => $this->when(
                $this->relationLoaded('employees'),
                fn () => $this->employees->pluck('name')->values()->all()
            ),
            'primary_employee_name' => $this->when(
                $this->relationLoaded('employees'),
                fn () => $this->employees->first()?->name
            ),
            'employees' => $this->when(
                $this->relationLoaded('employees'),
                fn () => $this->employees->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'email' => $e->email,
                ])
            ),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_at_label' => $this->updated_at
                ? 'آخر تعديل: '.$this->updated_at->locale(app()->getLocale())->translatedFormat('d F Y')
                : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
