<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Api\V2\Contract\Concerns\MapsContractStatusFields;
use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use App\Models\TenantRole;
use App\Support\DocFee;
use App\Support\HijriDobParts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step6Resource extends JsonResource
{
    use MapsContractStatusFields;
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        $docFee = DocFee::forContract($this->resource);
        $startingDate = HijriDobParts::split($this->contract_starting_date);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'contract_type' => $this->contract_type,

            'contract_starting_date' => $this->contract_starting_date,
            'contract_starting_date_day' => $startingDate['day'] ?? null,
            'contract_starting_date_month' => $startingDate['month'] ?? null,
            'contract_starting_date_year' => $startingDate['year'] ?? null,
            'type_contract_starting_date' => $this->type_contract_starting_date ?? 'hijri',

            'contract_term_in_years' => $this->contract_term_in_years,
            'price_contract_term' => $docFee['doc_fee']
                ?? optional($this->contractTermInYears)->price,

            'duration_preset' => $this->duration_preset,
            'duration_years' => $this->duration_years,
            'duration_months' => $this->duration_months,
            'total_months' => $this->total_months,

            'payment_type_id' => $this->payment_type_id,
            'annual_rent_amount_for_the_unit' => $this->annual_rent_amount_for_the_unit,
            'conditions' => (bool) (
                (is_array($this->other_conditions_list) && $this->other_conditions_list !== [])
                || ($this->other_conditions !== null && $this->other_conditions !== '')
            ),
            'other_conditions' => $this->other_conditions,
            'other_conditions_list' => $this->resolvedOtherConditionsList(),
            'other_conditions_count' => count($this->resolvedOtherConditionsList()),
            'additional_terms' => (bool) $this->additional_terms,
            'text_additional_terms' => $this->text_additional_terms,
            'daily_fine' => $this->daily_fine,
            'Guarantee_amount' => $this->Guarantee_amount,
            'deposit' => $this->deposit,
            'tenant_roles' => (bool) $this->tenant_roles,
            'tenant_role_id' => $this->tenant_role_id,
            'tenant_role_ids' => $this->tenant_role_ids ?? [],
            'tenant_role_values' => $this->tenant_role_values ?? [],
            'tenant_roles_details' => $this->tenantRolesDetails(),

            'doc_fee' => $docFee['doc_fee'] ?? null,
            'doc_fee_lines' => $docFee['doc_fee_lines'] ?? [],
            'billable_years' => $docFee['billable_years'] ?? null,
            'has_extra_months' => $docFee['has_extra_months'] ?? false,

            ...$this->contractStatusFields(),
            'step' => $this->step,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tenantRolesDetails(): array
    {
        $ids = $this->tenant_role_ids ?? [];
        if (! is_array($ids) || $ids === []) {
            return [];
        }

        $values = is_array($this->tenant_role_values) ? $this->tenant_role_values : [];
        $roles = TenantRole::query()->whereIn('id', $ids)->orderBy('id')->get();

        return $roles->map(function (TenantRole $role) use ($values) {
            $key = (string) $role->id;

            return [
                'id' => $role->id,
                'text_of_reason' => $role->text_of_reason,
                'name' => $role->text_of_reason,
                'service_definition' => $role->service_definition,
                'input_field_label' => $role->input_field_label,
                'input_field_type' => $role->input_field_type,
                'has_user_input' => $role->requiresUserInput(),
                'icon' => $role->icon,
                'input_icon' => $role->input_icon,
                'pop' => (bool) $role->pop,
                'value' => $values[$key] ?? $values[$role->id] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @return list<string>
     */
    private function resolvedOtherConditionsList(): array
    {
        $list = $this->other_conditions_list;
        if (is_array($list) && $list !== []) {
            return array_values(array_filter(array_map(
                static fn ($v) => is_scalar($v) ? trim((string) $v) : '',
                $list
            )));
        }

        if ($this->other_conditions !== null && trim((string) $this->other_conditions) !== '') {
            return [trim((string) $this->other_conditions)];
        }

        return [];
    }
}
