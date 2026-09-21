<?php

namespace App\Modules\Employees\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $action = $this->action;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'section_key' => $this->section,
            'section_label' => config("permissions.sections.{$this->section}.ar", $this->section_trans ?? $this->section),
            'action' => $action,
            'action_label' => $this->action_label_trans
                ?? config("permissions.actions.{$action}.ar", $action),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
