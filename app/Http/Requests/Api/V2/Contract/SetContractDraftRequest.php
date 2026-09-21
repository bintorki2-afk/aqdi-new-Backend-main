<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\ResolvesContractIdInput;

class SetContractDraftRequest extends BaseApiV2Request
{
    use ResolvesContractIdInput;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->resolveContractIdInput();

        if (! $this->exists('is_draft')) {
            return;
        }

        $value = $this->input('is_draft');
        if ($value === null || $value === '') {
            return;
        }

        if (is_bool($value) || is_int($value)) {
            $this->merge(['is_draft' => (bool) $value]);

            return;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                $this->merge(['is_draft' => true]);
            } elseif (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                $this->merge(['is_draft' => false]);
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'is_draft' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return $this->contractV2ArabicMessages([
            'id',
            'is_draft',
        ]);
    }
}
