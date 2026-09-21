<?php

namespace App\Support;

/**
 * Extra fields required when an admin changes a contract / draft status.
 *
 * Production IDs (also matched by Arabic name as a fallback):
 * - 9  توثيق العقد في إيجار
 * - 10 بانتظار المشرف
 * - 2  استرجاع / مسترجع
 * - 8  إرسال مسودة العقد لكم عبر واتساب
 */
class ContractStatusCase
{
    public const EJAR_AUTHENTICATION = 'ejar_authentication';

    public const WAITING_SUPERVISOR = 'waiting_supervisor';

    public const RETURN = 'return';

    public const SEND_DRAFT = 'send_draft';

    public const EJAR_AUTHENTICATION_ID = 9;

    public const WAITING_SUPERVISOR_ID = 10;

    public const RETURN_ID = 2;

    public const SEND_DRAFT_ID = 8;

    public const DEED_TYPES = ['paper', 'electronic', 'other'];

    public const CONTACT_MODES = ['same', 'another'];

    public static function resolve(?int $statusId, ?string $statusName): ?string
    {
        $normalized = self::normalizeName($statusName);

        if ($statusId === self::EJAR_AUTHENTICATION_ID || str_contains($normalized, 'توثيق العقد في ايجار')) {
            return self::EJAR_AUTHENTICATION;
        }

        if ($statusId === self::WAITING_SUPERVISOR_ID || str_contains($normalized, 'بانتظار المشرف')) {
            return self::WAITING_SUPERVISOR;
        }

        if (
            $statusId === self::SEND_DRAFT_ID
            || (str_contains($normalized, 'مسودة') && str_contains($normalized, 'واتساب'))
        ) {
            return self::SEND_DRAFT;
        }

        if (
            $statusId === self::RETURN_ID
            || str_contains($normalized, 'استرجاع')
            || str_contains($normalized, 'مسترجع')
        ) {
            return self::RETURN;
        }

        return null;
    }

    /**
     * Form schema for the admin UI (also appended on status list rows).
     *
     * @return array{key: string, fields: list<array<string, mixed>>}|null
     */
    public static function schemaFor(?int $statusId, ?string $statusName): ?array
    {
        $key = self::resolve($statusId, $statusName);
        if ($key === null) {
            return null;
        }

        return [
            'key' => $key,
            'fields' => self::fields($key),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fields(string $key): array
    {
        return match ($key) {
            self::EJAR_AUTHENTICATION => [
                [
                    'name' => 'deed_type',
                    'aliases' => ['deed_addition_method', 'addition_method'],
                    'type' => 'select',
                    'required' => true,
                    'label_ar' => 'نوع الصك / طريقة الإضافة',
                    'label_en' => 'Deed Type / Addition Method',
                    'options' => [
                        ['value' => 'paper', 'label_ar' => 'ورقي', 'label_en' => 'Paper'],
                        ['value' => 'electronic', 'label_ar' => 'إلكتروني', 'label_en' => 'Electronic'],
                        ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
                    ],
                ],
                [
                    'name' => 'deed_number',
                    'type' => 'string',
                    'required' => true,
                    'label_ar' => 'رقم الصك',
                    'label_en' => 'Deed Number',
                ],
            ],
            self::WAITING_SUPERVISOR => [
                [
                    'name' => 'ejar_contract_number',
                    'type' => 'string',
                    'required' => true,
                    'label_ar' => 'رقم عقد إيجار',
                    'label_en' => 'Ejar Contract Number',
                ],
                [
                    'name' => 'notes',
                    'aliases' => ['ejar_status_notes'],
                    'type' => 'text',
                    'required' => false,
                    'label_ar' => 'ملاحظات',
                    'label_en' => 'Notes',
                ],
            ],
            self::RETURN => [
                [
                    'name' => 'attachment',
                    'aliases' => ['file', 'status_attachment'],
                    'type' => 'file',
                    'required' => false,
                    'label_ar' => 'مرفق',
                    'label_en' => 'File Attachment',
                ],
            ],
            self::SEND_DRAFT => [
                [
                    'name' => 'ejar_contract_draft_number',
                    'aliases' => ['draft_number'],
                    'type' => 'string',
                    'required' => true,
                    'label_ar' => 'رقم مسودة عقد إيجار',
                    'label_en' => 'Ejar Contract Draft Number',
                ],
                [
                    'name' => 'contact_number_mode',
                    'aliases' => ['contact_choice'],
                    'type' => 'select',
                    'required' => true,
                    'label_ar' => 'رقم التواصل',
                    'label_en' => 'Contact Number',
                    'options' => [
                        ['value' => 'same', 'label_ar' => 'نفس الرقم', 'label_en' => 'Same number'],
                        ['value' => 'another', 'label_ar' => 'رقم آخر', 'label_en' => 'Another number'],
                    ],
                ],
                [
                    'name' => 'contact_number',
                    'aliases' => ['new_contact_number'],
                    'type' => 'string',
                    'required' => false,
                    'required_if' => ['contact_number_mode', 'another'],
                    'label_ar' => 'رقم التواصل الجديد',
                    'label_en' => 'New contact number',
                ],
            ],
            default => [],
        };
    }

    public static function normalizeDeedType(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = self::normalizeName($value);

        return match ($normalized) {
            'paper', 'ورقي', 'paper_deed' => 'paper',
            'electronic', 'الكتروني', 'صك الكتروني', 'electronic_deed' => 'electronic',
            'other', 'اخرى', 'other_deed' => 'other',
            default => in_array($normalized, self::DEED_TYPES, true) ? $normalized : null,
        };
    }

    public static function normalizeContactMode(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = self::normalizeName($value);

        return match ($normalized) {
            'same', 'same_number', 'same number', 'نفس الرقم', 'نفس' => 'same',
            'another', 'another_number', 'another number', 'other', 'رقم اخر' => 'another',
            default => in_array($normalized, self::CONTACT_MODES, true) ? $normalized : null,
        };
    }

    public static function normalizeName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $trimmed = trim($name);
        $trimmed = str_replace(['أ', 'إ', 'آ', 'ى'], ['ا', 'ا', 'ا', 'ي'], $trimmed);

        return preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
    }
}
