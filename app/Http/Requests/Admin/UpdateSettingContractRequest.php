<?php

namespace App\Http\Requests\Admin;

use App\Models\Contract;
use App\Models\SettingContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->hydrateJsonBodyIfNeeded();

        $merge = [];

        if ($this->exists('instrument_type')) {
            $merge['instrument_type'] = strtolower(trim((string) $this->input('instrument_type')));
        }

        foreach (['realestate', 'contract'] as $boolField) {
            if ($this->exists($boolField)) {
                $merge[$boolField] = $this->normalizeBoolean($this->input($boolField));
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var SettingContract|null $setting */
        $setting = $this->route('id')
            ? SettingContract::query()->find($this->route('id'))
            : null;

        return [
            'instrument_type' => [
                'sometimes',
                'string',
                Rule::in(Contract::instrumentTypes()),
                Rule::unique('setting_contracts', 'instrument_type')->ignore($setting?->id),
            ],
            'realestate' => ['sometimes', 'boolean'],
            'contract' => ['sometimes', 'boolean'],
            'label' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function hydrateJsonBodyIfNeeded(): void
    {
        if ($this->all() !== []) {
            return;
        }

        $raw = $this->getContent();
        if (! is_string($raw) || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded) && $decoded !== []) {
            $this->merge($decoded);
        }
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
