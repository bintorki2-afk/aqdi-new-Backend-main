<?php

namespace App\Services;

use App\Models\Contract;
use App\Support\ContractStatusCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class ContractStatusCaseService
{
    /**
     * @return array<string, mixed>
     */
    public function rules(?int $statusId, ?string $statusName): array
    {
        $case = ContractStatusCase::resolve($statusId, $statusName);

        return match ($case) {
            ContractStatusCase::EJAR_AUTHENTICATION => [
                'deed_type' => ['nullable', 'string', 'max:40'],
                'deed_addition_method' => ['nullable', 'string', 'max:40'],
                'addition_method' => ['nullable', 'string', 'max:40'],
                'deed_number' => ['required', 'string', 'max:255'],
            ],
            ContractStatusCase::WAITING_SUPERVISOR => [
                'ejar_contract_number' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
                'ejar_status_notes' => ['nullable', 'string'],
            ],
            ContractStatusCase::RETURN => [
                'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
                'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
                'status_attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            ],
            ContractStatusCase::SEND_DRAFT => [
                'ejar_contract_draft_number' => ['nullable', 'string', 'max:255'],
                'draft_number' => ['nullable', 'string', 'max:255'],
                'contact_number_mode' => ['nullable', 'string', 'max:40'],
                'contact_choice' => ['nullable', 'string', 'max:40'],
                'contact_number' => ['nullable', 'string', 'max:30'],
                'new_contact_number' => ['nullable', 'string', 'max:30'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'deed_number.required' => 'رقم الصك مطلوب.',
            'ejar_contract_number.required' => 'رقم عقد إيجار مطلوب.',
        ];
    }

    public function afterValidation(Validator $validator, Request $request, ?int $statusId, ?string $statusName, Contract $contract): void
    {
        $case = ContractStatusCase::resolve($statusId, $statusName);
        if ($case === null) {
            return;
        }

        if ($case === ContractStatusCase::EJAR_AUTHENTICATION) {
            $deedType = ContractStatusCase::normalizeDeedType(
                $this->firstFilled($request, ['deed_type', 'deed_addition_method', 'addition_method'])
            );
            if ($deedType === null) {
                $validator->errors()->add('deed_type', 'نوع الصك / طريقة الإضافة مطلوب (ورقي، إلكتروني، أخرى).');
            }
        }

        if ($case === ContractStatusCase::SEND_DRAFT) {
            $draftNumber = $this->firstFilled($request, ['ejar_contract_draft_number', 'draft_number']);
            if ($draftNumber === null || trim($draftNumber) === '') {
                $validator->errors()->add('ejar_contract_draft_number', 'رقم مسودة عقد إيجار مطلوب.');
            }

            $mode = ContractStatusCase::normalizeContactMode(
                $this->firstFilled($request, ['contact_number_mode', 'contact_choice'])
            );
            if ($mode === null) {
                $validator->errors()->add('contact_number_mode', 'يجب اختيار نفس الرقم أو رقم آخر.');

                return;
            }

            if ($mode === 'another') {
                $number = $this->firstFilled($request, ['contact_number', 'new_contact_number']);
                if ($number === null || trim($number) === '') {
                    $validator->errors()->add('contact_number', 'رقم التواصل الجديد مطلوب.');
                }
            }

            if ($mode === 'same' && $this->currentContactNumber($contract) === null) {
                $validator->errors()->add(
                    'contact_number_mode',
                    'لا يوجد رقم تواصل محفوظ على العقد. يرجى اختيار رقم آخر وإدخاله.'
                );
            }
        }
    }

    /**
     * Persist extra fields for the matched status case.
     *
     * @return array<string, mixed>
     */
    public function extract(Request $request, Contract $contract, ?int $statusId, ?string $statusName): array
    {
        $case = ContractStatusCase::resolve($statusId, $statusName);
        if ($case === null) {
            return [];
        }

        return match ($case) {
            ContractStatusCase::EJAR_AUTHENTICATION => [
                'deed_addition_method' => ContractStatusCase::normalizeDeedType(
                    $this->firstFilled($request, ['deed_type', 'deed_addition_method', 'addition_method'])
                ),
                'deed_number' => trim((string) $request->input('deed_number')),
            ],
            ContractStatusCase::WAITING_SUPERVISOR => $this->extractWaitingSupervisor($request),
            ContractStatusCase::RETURN => $this->extractAttachment($request, $contract),
            ContractStatusCase::SEND_DRAFT => $this->extractSendDraft($request, $contract),
            default => [],
        };
    }

    /**
     * Snapshot stored on contract_status_histories.meta.
     *
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>|null
     */
    public function historyMeta(?int $statusId, ?string $statusName, array $updates): ?array
    {
        $case = ContractStatusCase::resolve($statusId, $statusName);
        if ($case === null || $updates === []) {
            return null;
        }

        $meta = ['case' => $case];

        foreach ($updates as $key => $value) {
            if ($value !== null && $value !== '') {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    public function currentContactNumber(Contract $contract): ?string
    {
        $candidates = [
            $contract->tenant_mobile,
            $contract->mobile_of_property_tenant_agent,
            $contract->user?->mobile,
        ];

        foreach ($candidates as $number) {
            if (is_string($number) && trim($number) !== '') {
                return trim($number);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractWaitingSupervisor(Request $request): array
    {
        $updates = [
            'ejar_contract_number' => trim((string) $request->input('ejar_contract_number')),
        ];

        if ($request->exists('notes') || $request->exists('ejar_status_notes')) {
            $updates['ejar_status_notes'] = $this->nullableString(
                $this->firstFilled($request, ['notes', 'ejar_status_notes'])
            );
        }

        return $updates;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSendDraft(Request $request, Contract $contract): array
    {
        $mode = ContractStatusCase::normalizeContactMode(
            $this->firstFilled($request, ['contact_number_mode', 'contact_choice'])
        );
        $number = $mode === 'another'
            ? trim((string) $this->firstFilled($request, ['contact_number', 'new_contact_number']))
            : $this->currentContactNumber($contract);

        return [
            'ejar_contract_draft_number' => trim((string) $this->firstFilled(
                $request,
                ['ejar_contract_draft_number', 'draft_number']
            )),
            'draft_contact_number_mode' => $mode,
            'draft_contact_number' => $number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAttachment(Request $request, Contract $contract): array
    {
        $file = $this->uploadedAttachment($request);
        if (! $file instanceof UploadedFile) {
            return [];
        }

        if (is_string($contract->status_attachment) && $contract->status_attachment !== '') {
            deleteFile($contract->status_attachment);
        }

        return [
            'status_attachment' => fileUploader($file, 'contracts/status-attachments'),
        ];
    }

    private function uploadedAttachment(Request $request): ?UploadedFile
    {
        foreach (['attachment', 'file', 'status_attachment'] as $key) {
            $file = $request->file($key);
            if ($file instanceof UploadedFile) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $keys
     */
    private function firstFilled(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
