<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\Contract;
use App\Models\RealEstate;
use App\Models\UnitsReal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ContractTypeRequest extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('instrument_type')) {
            $this->merge([
                'instrument_type' => Contract::normalizeInstrumentType($this->input('instrument_type')),
            ]);
        }

        $unitIds = $this->resolveIncomingUnitIds();

        if ($unitIds !== []) {
            $this->merge([
                'unit_ids' => $unitIds,
                // Keep legacy pointer as the first selected unit.
                'real_units_id' => $unitIds[0],
            ]);
        }
    }

    /**
     * Normalize unit_ids / units[] / real_units_id into a unique list of IDs.
     *
     * @return list<int>
     */
    private function resolveIncomingUnitIds(): array
    {
        $ids = [];

        if ($this->filled('unit_ids') && is_array($this->input('unit_ids'))) {
            foreach ($this->input('unit_ids') as $id) {
                if ($id !== null && $id !== '') {
                    $ids[] = (int) $id;
                }
            }
        }

        if ($this->filled('units') && is_array($this->input('units'))) {
            foreach ($this->input('units') as $unit) {
                if (! is_array($unit)) {
                    continue;
                }
                $existingId = $unit['unit_id'] ?? $unit['id'] ?? $unit['real_unit_id'] ?? null;
                if ($existingId !== null && $existingId !== '') {
                    $ids[] = (int) $existingId;
                }
            }
        }

        if ($this->filled('real_units_id')) {
            $ids[] = (int) $this->input('real_units_id');
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $isReal = (bool) $this->input('is_real');

        return [
            'contract_type' => ['required', Rule::in(Contract::contractTypes())],
            'instrument_type' => ['nullable', Rule::in(Contract::instrumentTypes())],
            'is_real' => 'nullable|boolean',
            'real_id' => [
                Rule::requiredIf(fn () => $isReal),
                'nullable',
                Rule::exists('real_estates', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            // Legacy single-unit field (still accepted).
            'real_units_id' => [
                'nullable',
                'integer',
                'exists:real_units,id',
            ],
            'unit_ids' => [
                Rule::requiredIf(fn () => $isReal),
                'nullable',
                'array',
                'min:1',
                'max:50',
            ],
            'unit_ids.*' => ['integer', 'exists:real_units,id'],
            'units' => ['nullable', 'array', 'max:50'],
            'units.*.unit_id' => ['nullable', 'integer', 'exists:real_units,id'],
            'units.*.id' => ['nullable', 'integer', 'exists:real_units,id'],
            'units.*.real_unit_id' => ['nullable', 'integer', 'exists:real_units,id'],
        ];
    }

    public function messages(): array
    {
        return array_merge(
            $this->contractV2ArabicMessages([
                'contract_type',
                'instrument_type',
                'is_real',
                'real_id',
                'real_units_id',
                'unit_ids',
            ]),
            [
                'unit_ids.required' => 'يجب اختيار وحدة واحدة على الأقل من العقار.',
                'unit_ids.min' => 'يجب اختيار وحدة واحدة على الأقل من العقار.',
                'unit_ids.*.exists' => 'إحدى الوحدات المختارة غير موجودة.',
            ]
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! (bool) $this->input('is_real')) {
                return;
            }

            $unitIds = $this->resolvedUnitIds();
            if ($unitIds === []) {
                $validator->errors()->add('unit_ids', 'يجب اختيار وحدة واحدة على الأقل من العقار.');

                return;
            }

            $realId = $this->filled('real_id') ? (int) $this->input('real_id') : null;
            if (! $realId) {
                return;
            }

            $userId = auth()->id();

            if ($realId && ! RealEstate::query()->whereKey($realId)->where('user_id', $userId)->exists()) {
                $validator->errors()->add('real_id', trans('api.not_have_real'));

                return;
            }

            $ownedCount = UnitsReal::query()
                ->whereIn('id', $unitIds)
                ->where('user_id', $userId)
                ->where('real_estates_units_id', $realId)
                ->count();

            if ($ownedCount !== count($unitIds)) {
                $validator->errors()->add(
                    'unit_ids',
                    'جميع الوحدات يجب أن تكون تابعة للعقار المحدد ومملوكة للمستخدم.'
                );
            }

            if (! in_array($this->input('instrument_type'), ['electronic', 'strong_argument'], true)) {
                return;
            }

            $real = RealEstate::query()->find($realId);
            if (! $real || ! $real->hasResolvableUnitsCount($unitIds[0] ?? null)) {
                $validator->errors()->add(
                    'number_of_units_in_realestate',
                    'عدد الوحدات غير محدد في العقار المرتبط بالعقد.'
                );
            }
        });
    }

    /**
     * Unit IDs resolved after prepareForValidation.
     *
     * @return list<int>
     */
    public function resolvedUnitIds(): array
    {
        $ids = $this->input('unit_ids', []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }

    /**
     * Payloads for ContractUnitsService::syncForContract.
     *
     * @return list<array{unit_id: int}>
     */
    public function unitPayloadsForSync(): array
    {
        return array_map(
            static fn (int $id) => ['unit_id' => $id],
            $this->resolvedUnitIds()
        );
    }
}
