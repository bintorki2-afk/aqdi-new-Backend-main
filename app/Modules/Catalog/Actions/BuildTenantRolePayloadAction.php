<?php

namespace App\Modules\Catalog\Actions;

use Illuminate\Http\Request;

class BuildTenantRolePayloadAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $type = $request->input('input_field_type');
        $label = $request->input('input_field_label');

        $type = ($type === '' || $type === null) ? null : (string) $type;
        $label = ($label === '' || $label === null) ? null : trim((string) $label);

        if ($type === null || $label === null) {
            $type = null;
            $label = null;
        }

        $definition = $request->input('service_definition');
        $definition = ($definition === '' || $definition === null) ? null : trim((string) $definition);

        $icon = $request->input('icon');
        $icon = ($icon === '' || $icon === null) ? null : trim((string) $icon);

        $inputIcon = $request->input('input_icon');
        $inputIcon = ($inputIcon === '' || $inputIcon === null) ? null : trim((string) $inputIcon);

        return [
            'text_of_reason' => trim((string) $request->input('text_of_reason')),
            'service_definition' => $definition,
            'input_field_label' => $label,
            'input_field_type' => $type,
            'icon' => $icon,
            'input_icon' => $inputIcon,
            'pop' => $request->boolean('pop'),
        ];
    }
}
