<?php

namespace App\Modules\Employees\Requests\Admin\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($id)],
            'title_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            'permission_matrix' => ['nullable', 'array'],
            'permission_matrix.*' => ['array'],
            'permission_matrix.*.*' => ['string', Rule::in(['view', 'create', 'edit', 'delete', 'retrieve'])],
            'activate_all_permissions' => ['sometimes', 'boolean'],
        ];
    }
}
