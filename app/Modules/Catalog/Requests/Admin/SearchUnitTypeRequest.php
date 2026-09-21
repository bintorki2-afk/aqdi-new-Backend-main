<?php

namespace App\Modules\Catalog\Requests\Admin;


class SearchUnitTypeRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => 'required|string|min:1',
        ];
    }
}
