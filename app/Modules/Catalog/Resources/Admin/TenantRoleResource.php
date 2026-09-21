<?php

namespace App\Modules\Catalog\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasInput = $this->requiresUserInput();

        return [
            'id' => $this->id,
            'text_of_reason' => $this->text_of_reason,
            'name' => $this->text_of_reason,
            'service_definition' => $this->service_definition,
            'input_field_label' => $this->input_field_label,
            'input_field_type' => $this->input_field_type,
            'has_user_input' => $hasInput,
            'icon' => $this->icon,
            'input_icon' => $this->input_icon,
            'pop' => (bool) $this->pop,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
