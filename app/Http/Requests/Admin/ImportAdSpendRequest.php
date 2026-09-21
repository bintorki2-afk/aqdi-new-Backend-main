<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportAdSpendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $platforms = array_keys(config('ads.platforms', []));

        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.spent_on' => ['required', 'date'],
            'rows.*.platform' => ['required', 'string', Rule::in($platforms)],
            'rows.*.spend' => ['required', 'numeric', 'min:0'],
            'rows.*.campaign_id' => ['nullable', 'string', 'max:64'],
            'rows.*.campaign_name' => ['nullable', 'string', 'max:191'],
            'rows.*.keyword' => ['nullable', 'string', 'max:191'],
            'rows.*.currency' => ['nullable', 'string', 'size:3'],
            'rows.*.impressions' => ['nullable', 'integer', 'min:0'],
            'rows.*.clicks' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
