<?php

namespace App\Modules\Contracts\Actions\Api\V2;

use App\Http\Requests\Api\V2\Contract\Step6Request;
use App\Models\Contract;
use App\Models\TenantRole;
use App\Support\ContractStartingDateInput;
use App\Support\DocFee;

class SubmitContractStep6Action
{
    /**
     * @return array{ok: true, contract: Contract}|array{ok: false, message: string, code?: int}
     */
    public function execute(Contract $contract, Step6Request $request): array
    {
        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract')];
        }

        $isOther = $request->input('duration_preset') === 'other';
        $docFee = null;

        $data = [
            'contract_starting_date' => ContractStartingDateInput::resolveForStorage($request),
            'type_contract_starting_date' => $request->input('type_contract_starting_date', 'hijri'),
            'payment_type_id' => $request->payment_type_id,
            'additional_terms' => $request->additional_terms ?? 0,
            'text_additional_terms' => $request->text_additional_terms,
            'tenant_roles' => $request->boolean('tenant_roles'),
            'step' => 7,
        ];

        if ($request->filled('annual_rent_amount_for_the_unit')) {
            $data['annual_rent_amount_for_the_unit'] = $request->annual_rent_amount_for_the_unit;
        }

        if ($isOther) {
            $years = (int) $request->input('duration_years', 0);
            $months = (int) $request->input('duration_months', 0);
            $docFee = DocFee::summarize((string) $contract->contract_type, 'other', $years, $months);

            $data['duration_preset'] = 'other';
            $data['duration_years'] = $docFee['duration_years'];
            $data['duration_months'] = $docFee['duration_months'];
            $data['total_months'] = $docFee['total_months'];
            $data['contract_term_in_years'] = $request->filled('contract_term_in_years')
                ? $request->contract_term_in_years
                : null;
        } else {
            $data['contract_term_in_years'] = $request->contract_term_in_years;
            $data['duration_preset'] = null;
            $data['duration_years'] = null;
            $data['duration_months'] = null;
            $data['total_months'] = null;
        }

        [$tenantRoleIds, $firstTenantRoleId] = $this->normalizeTenantRoleIdsFromStep6Request($request);
        $data['tenant_role_ids'] = $tenantRoleIds !== [] ? $tenantRoleIds : null;
        $data['tenant_role_id'] = $firstTenantRoleId;
        $data['tenant_role_values'] = $this->normalizeTenantRoleValuesFromStep6Request($request, $tenantRoleIds);

        $otherConditionsList = $request->resolvedOtherConditionsList();
        if ((bool) $request->input('conditions') && $otherConditionsList !== []) {
            $data['other_conditions_list'] = $otherConditionsList;
            $data['other_conditions'] = $otherConditionsList[0];
        } else {
            $data['other_conditions_list'] = null;
            $data['other_conditions'] = null;
        }

        if ($request->filled('daily_fine')) {
            $data['daily_fine'] = $request->daily_fine;
        }

        // Guarantee (الضمان) and deposit were never captured before, so they never
        // reached the dashboard. Persist them here.
        if ($request->filled('Guarantee_amount')) {
            $data['Guarantee_amount'] = $request->input('Guarantee_amount');
        }

        if ($request->filled('deposit')) {
            $data['deposit'] = $request->input('deposit');
        }

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract->fresh(['realEstate', 'contractStatus', 'contractTermInYears'])];
    }

    /**
     * @return array{0: list<int>, 1: int|null}
     */
    private function normalizeTenantRoleIdsFromStep6Request(Step6Request $request): array
    {
        $raw = $request->input('tenant_role_ids');
        $ids = is_array($raw) ? $raw : [];

        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids))));

        $first = $ids[0] ?? null;

        return [$ids, $first];
    }

    /**
     * @param  list<int>  $roleIds
     * @return array<string, string>|null
     */
    private function normalizeTenantRoleValuesFromStep6Request(Step6Request $request, array $roleIds): ?array
    {
        if ($roleIds === []) {
            return null;
        }

        $raw = $request->input('tenant_role_values', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        $roles = TenantRole::query()->whereIn('id', $roleIds)->get()->keyBy('id');
        $normalized = [];

        foreach ($roleIds as $roleId) {
            $role = $roles->get($roleId);
            if (! $role || ! $role->requiresUserInput()) {
                continue;
            }

            $value = $raw[(string) $roleId] ?? $raw[$roleId] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $normalized[(string) $roleId] = is_scalar($value) ? (string) $value : '';
        }

        return $normalized !== [] ? $normalized : null;
    }
}
