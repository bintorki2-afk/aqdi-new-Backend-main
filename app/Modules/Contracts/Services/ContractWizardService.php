<?php

namespace App\Modules\Contracts\Services;

use App\Http\Requests\Api\V2\Contract\ContractTypeRequest;
use App\Http\Requests\Api\V2\Contract\SetContractDraftRequest;
use App\Http\Requests\Api\V2\Contract\Step1Request;
use App\Http\Requests\Api\V2\Contract\Step2Request;
use App\Http\Requests\Api\V2\Contract\Step3Request;
use App\Http\Requests\Api\V2\Contract\Step4Request;
use App\Http\Requests\Api\V2\Contract\Step5Request;
use App\Http\Requests\Api\V2\Contract\Step6Request;
use App\Models\Contract;
use App\Models\DraftContractStatus;
use App\Models\RealEstate;
use App\Models\TenantRole;
use App\Services\ContractUnitsService;
use App\Services\FirebaseNotificationService;
use App\Support\ContractStartingDateInput;
use App\Support\DateInputNormalizer;
use App\Support\DocFee;
use App\Support\HijriDobParts;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ContractWizardService
{
    public function __construct(
        private readonly ContractPropertyAddressService $address,
        private readonly ContractUnitsService $units,
        private readonly FirebaseNotificationService $firebase,
    ) {}

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function start(ContractTypeRequest $request, int $userId): array
    {
        $validated = $request->validated();

        $instrumentType = $request->filled('instrument_type')
            ? ($validated['instrument_type'] ?? null)
            : null;

        if (! $instrumentType && ! empty($validated['real_id'])) {
            $instrumentType = RealEstate::query()
                ->whereKey($validated['real_id'])
                ->value('instrument_type');
        }

        $unitPayloads = $request->unitPayloadsForSync();
        $primaryUnitId = $unitPayloads[0]['unit_id'] ?? (
            ! empty($validated['real_units_id']) ? (int) $validated['real_units_id'] : null
        );

        if (! empty($validated['real_id'])) {
            $realEstate = RealEstate::query()->find($validated['real_id']);
            $realEstate?->syncNumberOfUnitsInRealestate($primaryUnitId);
        }

        $contract = Contract::create([
            'contract_type' => $validated['contract_type'],
            'instrument_type' => $instrumentType,
            'is_real' => (bool) ($validated['is_real'] ?? false),
            'real_id' => $validated['real_id'] ?? null,
            'real_units_id' => $primaryUnitId,
            'user_id' => $userId,
            'step' => Contract::shouldSkipInitialSteps($instrumentType) ? 3 : 1,
        ]);

        if ($unitPayloads !== []) {
            try {
                $this->units->syncForContract($contract, $unitPayloads, $userId);
            } catch (InvalidArgumentException $e) {
                return ['ok' => false, 'message' => $e->getMessage(), 'code' => 422];
            }
        }

        $contract->load(['units.unitType', 'units.unitUsage']);

        return ['ok' => true, 'contract' => $contract];
    }

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function step1(Step1Request $request, int $userId): array
    {
        $validated = $request->validated();
        $contract = Contract::findOwnedOrFail($validated['id'], $userId);

        $step1Data = [
            'app_or_web' => 'app',
            'is_multiple_trusteeship_deed_copy' => array_key_exists('is_multiple_trusteeship_deed_copy', $validated)
                ? (bool) $validated['is_multiple_trusteeship_deed_copy']
                : (bool) $contract->is_multiple_trusteeship_deed_copy,
        ];

        foreach ([
            'property_type_id',
            'property_usages_id',
            'age_of_the_property',
            'number_of_floors',
            'number_of_units_per_floor',
            'number_of_units_in_realestate',
            'instrument_number',
            'type_instrument_history',
            'real_estate_registry_number',
            'type_date_first_registration',
        ] as $optionalField) {
            if (array_key_exists($optionalField, $validated)) {
                $step1Data[$optionalField] = $validated[$optionalField];
            }
        }

        $instrumentHistory = $request->resolvedInstrumentHistory();
        if ($instrumentHistory !== null) {
            $step1Data['instrument_history'] = $instrumentHistory;
            if (! array_key_exists('type_instrument_history', $step1Data)) {
                $step1Data['type_instrument_history'] = $request->input('type_instrument_history', 'hijri');
            }
        }

        $dateFirstRegistration = $request->resolvedDateFirstRegistration();
        if ($dateFirstRegistration !== null) {
            $step1Data['date_first_registration'] = $dateFirstRegistration;
            if (! array_key_exists('type_date_first_registration', $step1Data)) {
                $step1Data['type_date_first_registration'] = $request->input('type_date_first_registration', 'hijri');
            }
        }

        if ($request->filled('instrument_type')) {
            $step1Data['instrument_type'] = $validated['instrument_type'];
        }

        $this->address->applyCoordinatesIfPresent($step1Data, $request, $validated);
        $this->address->applyAddressUrlIfPresent($step1Data, $request, $validated);

        if ($addressError = $this->address->applyIfPresent($step1Data, $request, $validated, $contract)) {
            return ['ok' => false, 'message' => $addressError, 'code' => 400];
        }

        $effectiveInstrumentType = $step1Data['instrument_type'] ?? $contract->instrument_type;
        $step1Data['step'] = Contract::shouldSkipInitialSteps($effectiveInstrumentType) ? 3 : 2;

        if ($contract->real_id) {
            $contract->loadMissing('realEstate');
            $fromReal = $contract->realEstate?->number_of_units_in_realestate;
            if ($fromReal !== null && $fromReal !== '') {
                $step1Data['number_of_units_in_realestate'] = $fromReal;
            }
        }

        $imageInstrumentFile = $request->file('image_instrument');
        if ($imageInstrumentFile instanceof \Illuminate\Http\UploadedFile && $imageInstrumentFile->isValid()) {
            $step1Data['image_instrument'] = $imageInstrumentFile->store('images/contracts', 'public');
        } elseif (array_key_exists('image_instrument', $validated) && is_string($validated['image_instrument']) && $validated['image_instrument'] !== '') {
            $step1Data['image_instrument'] = $validated['image_instrument'];
        }

        foreach (['image_instrument_from_the_front', 'image_instrument_from_the_back'] as $deedImageField) {
            if ($request->hasFile($deedImageField)) {
                $step1Data[$deedImageField] = $request->file($deedImageField)->store('images/contracts', 'public');
            } elseif (
                array_key_exists($deedImageField, $validated)
                && is_string($validated[$deedImageField])
                && $validated[$deedImageField] !== ''
            ) {
                $step1Data[$deedImageField] = $validated[$deedImageField];
            }
        }

        if ($request->hasFile('copy_of_the_endowment_registration_certificate')) {
            $step1Data['copy_of_the_endowment_registration_certificate'] = $request->file('copy_of_the_endowment_registration_certificate')
                ->store('contracts/endowment-registration-certificates', 'public');
        }

        if ($request->hasFile('copy_of_the_trusteeship_deed')) {
            $step1Data['copy_of_the_trusteeship_deed'] = $request->file('copy_of_the_trusteeship_deed')
                ->store('contracts/trusteeship-deeds', 'public');
        }

        foreach ([
            'Image_inheritance_certificate' => 'contracts/inheritance-certificates',
            'copy_power_of_attorney_from_heirs_to_agent' => 'contracts/heirs-powers-of-attorney',
            'copy_of_guardians_power_of_attorney_for_agent' => 'contracts/guardians-powers-of-attorney',
        ] as $instrumentFileField => $storageDir) {
            if ($request->hasFile($instrumentFileField)) {
                $step1Data[$instrumentFileField] = $request->file($instrumentFileField)->store($storageDir, 'public');
            }
        }

        if ($request->hasFile('image_address')) {
            $step1Data['image_address'] = $request->file('image_address')->store('images/contracts', 'public');
        }

        $contract->update($step1Data);

        return ['ok' => true, 'contract' => $contract->fresh(['realEstate', 'contractStatus'])];
    }

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function step2(Step2Request $request): array
    {
        $validated = $request->validated();
        $contract = Contract::findOwnedOrFail($validated['id']);

        if (Contract::shouldSkipInitialSteps($contract->instrument_type)) {
            $skipData = ['step' => 3];
            $this->address->applyCoordinatesIfPresent($skipData, $request, $validated);
            $this->address->applyAddressUrlIfPresent($skipData, $request, $validated);
            if ($addressError = $this->address->applyIfPresent($skipData, $request, $validated, $contract)) {
                return ['ok' => false, 'message' => $addressError, 'code' => 400];
            }
            $contract->update($skipData);

            return ['ok' => true, 'contract' => $contract->fresh(['contractStatus'])];
        }

        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
        }

        $data = ['step' => 3];
        $this->address->mergeRequired($data, $validated);
        if ($addressError = $this->address->applyIfPresent($data, $request, $validated, $contract)) {
            return ['ok' => false, 'message' => $addressError, 'code' => 400];
        }

        $this->address->applyCoordinatesIfPresent($data, $request, $validated);
        $this->address->applyAddressUrlIfPresent($data, $request, $validated);

        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/contracts', 'public');
        }

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract->fresh(['contractStatus'])];
    }

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function step3(Step3Request $request): array
    {
        $contract = Contract::findOwnedOrFail($request->id);

        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
        }

        $data = $this->buildStep3BaseData($request, $contract);

        $shouldApplyAgentBlock = $contract->instrument_type !== 'lease_renewal'
            || $request->has('add_legal_agent_of_owner');

        if ($shouldApplyAgentBlock) {
            $data = $this->hasOwnerAgent($request)
                ? $this->appendStep3AgentData($data, $request, $contract)
                : $this->appendStep3NoAgentData($data);
        }

        $contract->update($data);
        $this->syncStep3RealEstateName($contract, $request);

        return ['ok' => true, 'contract' => $contract->fresh(['contractStatus'])];
    }

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function step4(Step4Request $request): array
    {
        $contract = Contract::findOwnedOrFail($request->id);

        if ($contract->instrument_type === 'lease_renewal') {
            if ($contract->is_completed) {
                return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
            }

            $leaseRenewalData = ['step' => 5];
            if ($request->has('notes_edits')) {
                $leaseRenewalData['notes_edits'] = $request->input('notes_edits');
            }

            $contract->update($leaseRenewalData);

            return ['ok' => true, 'contract' => $contract->fresh(['contractStatus'])];
        }

        $validatedData = $request->validated();

        $tenantDobCombined = (
            $request->filled('tenant_dob_day')
            && $request->filled('tenant_dob_month')
            && $request->filled('tenant_dob_year')
        )
            ? HijriDobParts::combine(
                $request->input('tenant_dob_day'),
                $request->input('tenant_dob_month'),
                $request->input('tenant_dob_year')
            )
            : null;

        $tenantAgentDobCombined = (
            $request->filled('dobof_property_tenant_agent_day')
            && $request->filled('dobof_property_tenant_agent_month')
            && $request->filled('dobof_property_tenant_agent_year')
        )
            ? HijriDobParts::combine(
                $request->input('dobof_property_tenant_agent_day'),
                $request->input('dobof_property_tenant_agent_month'),
                $request->input('dobof_property_tenant_agent_year')
            )
            : null;

        unset(
            $validatedData['tenant_dob'],
            $validatedData['tenant_dob_day'],
            $validatedData['tenant_dob_month'],
            $validatedData['tenant_dob_year'],
            $validatedData['dobof_property_tenant_agent_day'],
            $validatedData['dobof_property_tenant_agent_month'],
            $validatedData['dobof_property_tenant_agent_year']
        );

        if ($request->hasFile('copy_of_the_owner_record')) {
            $validatedData['copy_of_the_owner_record'] = $request->file('copy_of_the_owner_record')->store('copy_of_the_owner_record', 'public');
        }

        $data = array_merge($validatedData, [
            'step' => 5,
            'tenant_dob' => $tenantDobCombined,
            'dob_of_property_tenant_agent' => $tenantAgentDobCombined,
            'type_tenant_dob' => $request->input('type_tenant_dob', 'hijri'),
            'type_dob_tenant_agent' => $request->input('type_dob_tenant_agent', 'hijri'),
            'copy_of_the_owner_record' => $validatedData['copy_of_the_owner_record'] ?? $contract->copy_of_the_owner_record,
        ]);

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract->fresh(['contractStatus'])];
    }

    /**
     * @return array{ok: bool, contract?: Contract, units?: mixed, message?: string, code?: int}
     */
    public function step5(Step5Request $request, int $userId): array
    {
        $contract = Contract::findOwnedOrFail($request->integer('id'), $userId);

        $unitsPayload = $request->input('units', []);
        if (! is_array($unitsPayload) || $unitsPayload === []) {
            return ['ok' => false, 'message' => 'يجب إرسال وحدة واحدة على الأقل.', 'code' => 422];
        }

        try {
            $units = $this->units->syncForContract($contract, $unitsPayload, $userId);
        } catch (InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'code' => 422];
        }

        $contract->update(['step' => 6]);

        return [
            'ok' => true,
            'contract' => $contract->fresh(['contractStatus', 'units.unitType', 'units.unitUsage', 'units.realEstate']),
            'units' => $units,
        ];
    }

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function step6(Step6Request $request): array
    {
        $contract = Contract::findOwnedOrFail($request->id);

        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
        }

        $isOther = $request->input('duration_preset') === 'other';

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

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract->fresh(['realEstate', 'contractStatus', 'contractTermInYears'])];
    }

    /**
     * @return array{ok: bool, contract?: Contract, message?: string, code?: int}
     */
    public function setDraft(SetContractDraftRequest $request, int $userId): array
    {
        $contract = Contract::findOwnedOrFail($request->id, $userId);

        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
        }

        $wasDraft = (bool) $contract->is_draft;
        $isDraft = $request->boolean('is_draft');

        $updates = [
            'is_draft' => $isDraft,
        ];

        if ($isDraft && ! $contract->draft_contract_status_id) {
            $newDraftStatusId = DraftContractStatus::newStatusId();
            if ($newDraftStatusId !== null) {
                $updates['draft_contract_status_id'] = $newDraftStatusId;
            }
        }

        $contract->update($updates);

        if ($isDraft && ! $wasDraft) {
            $this->notifyEmployeesNewContract($contract->fresh(['user']));
        }

        return ['ok' => true, 'contract' => $contract->fresh(['realEstate', 'contractStatus'])];
    }

    public function notifyEmployeesNewContract(Contract $contract, ?float $paidAmount = null): void
    {
        try {
            $this->firebase->notifyEmployeesOfNewContract($contract, $paidAmount);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify employees of new contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStep3BaseData(Step3Request $request, Contract $contract): array
    {
        $dob = $request->resolvedPropertyOwnerDobString();
        $ownerDobTypeNorm = $this->normalizeOwnerCalendarType(
            $request->input('type_dob_property_owner', $request->input('type_dob'))
        );

        $typePayload = [
            'type_dob_property_owner' => $ownerDobTypeNorm,
            'type_dob' => $ownerDobTypeNorm,
        ];

        $dobPayload = [
            'property_owner_dob' => $dob,
        ];

        if ($contract->instrument_type === 'lease_renewal') {
            $data = array_merge([
                'step' => 5,
            ], $typePayload, $dobPayload);
            if ($request->filled('name_owner')) {
                $data['name_owner'] = $request->name_owner;
            }
            if ($request->filled('property_owner_id_num')) {
                $data['property_owner_id_num'] = $request->property_owner_id_num;
            }
            if ($request->filled('property_owner_mobile')) {
                $data['property_owner_mobile'] = $request->property_owner_mobile;
            }
            if ($request->has('property_owner_iban')) {
                $data['property_owner_iban'] = $request->property_owner_iban;
            }
            if ($request->has('add_legal_agent_of_owner')) {
                $data['add_legal_agent_of_owner'] = $request->input('add_legal_agent_of_owner');
            }

            return $data;
        }

        return array_merge([
            'name_owner' => $request->name_owner,
            'property_owner_id_num' => $request->property_owner_id_num,
            'property_owner_mobile' => $request->property_owner_mobile,
            'property_owner_iban' => $request->property_owner_iban,
            'add_legal_agent_of_owner' => $request->add_legal_agent_of_owner,
            'step' => 4,
        ], $typePayload, $dobPayload);
    }

    private function normalizeOwnerCalendarType(mixed $value): string
    {
        $raw = strtolower(trim((string) ($value ?? 'hijri')));

        return in_array($raw, ['hijri', 'gregorian'], true) ? $raw : 'hijri';
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

    private function hasOwnerAgent(Step3Request $request): bool
    {
        $add = $request->add_legal_agent_of_owner;

        return in_array((string) $add, ['1', 'true'], true)
            || $add === 1
            || $add === true;
    }

    private function appendStep3AgentData(array $data, Step3Request $request, Contract $contract): array
    {
        $data['id_num_of_property_owner_agent'] = $request->id_num_of_property_owner_agent;
        $data['type_dob_property_owner_agent'] = $request->input('type_dob_property_owner_agent', 'hijri');
        $data['dob_of_property_owner_agent'] = HijriDobParts::combine(
            $request->input('dob_of_property_owner_agent_day'),
            $request->input('dob_of_property_owner_agent_month'),
            $request->input('dob_of_property_owner_agent_year')
        );
        $data['mobile_of_property_owner_agent'] = $request->mobile_of_property_owner_agent;
        $data['agency_number_in_instrument_of_property_owner'] = $request->agency_number_in_instrument_of_property_owner;
        $data['type_agency_instrument_date_of_property_owner'] = $request->input(
            'type_agency_instrument_date_of_property_owner',
            'hijri'
        );
        $data['agency_instrument_date_of_property_owner'] = DateInputNormalizer::combineFromParts(
            $request->input('agency_instrument_date_of_property_owner_day'),
            $request->input('agency_instrument_date_of_property_owner_month'),
            $request->input('agency_instrument_date_of_property_owner_year')
        );

        $data['copy_of_the_authorization_or_agency'] = $request->hasFile('copy_of_the_authorization_or_agency')
            ? $request->file('copy_of_the_authorization_or_agency')->store('authorizations', 'public')
            : $contract->copy_of_the_authorization_or_agency;

        return $data;
    }

    private function appendStep3NoAgentData(array $data): array
    {
        $data['id_num_of_property_owner_agent'] = null;
        $data['type_dob_property_owner_agent'] = null;
        $data['dob_of_property_owner_agent'] = null;
        $data['mobile_of_property_owner_agent'] = null;
        $data['agency_number_in_instrument_of_property_owner'] = null;
        $data['agency_instrument_date_of_property_owner'] = null;
        $data['type_agency_instrument_date_of_property_owner'] = null;
        $data['copy_of_the_authorization_or_agency'] = null;

        return $data;
    }

    private function syncStep3RealEstateName(Contract $contract, Step3Request $request): void
    {
        if (! $contract->real_id) {
            return;
        }

        if ($contract->instrument_type === 'lease_renewal' && ! $request->filled('name_real_estate')) {
            return;
        }

        RealEstate::query()
            ->whereKey($contract->real_id)
            ->where('user_id', $contract->user_id)
            ->update([
            'name_real_estate' => $request->name_real_estate,
        ]);
    }
}
