<?php

namespace App\Support;

class TypeFurnished
{
    /**
     * Accept boolean or string (e.g. true / "new" / "0").
     *
     * @return list<string|\Closure>
     */
    public static function rules(bool $sometimes = false): array
    {
        $rules = [
            $sometimes ? 'sometimes' : 'nullable',
            'nullable',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
                    if (is_string($value) && mb_strlen($value) > 255) {
                        $fail('حقل نوع التأثيث يجب ألا يزيد عن 255 حرفاً.');
                    }

                    return;
                }

                $fail('حقل نوع التأثيث يجب أن يكون نصاً أو قيمة منطقية (boolean).');
            },
        ];

        return $rules;
    }

    /**
     * Normalize for DB string columns: keep text, map bool/0/1 to "1"/"0".
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) (int) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            $lower = strtolower($trimmed);
            if (in_array($lower, ['true', 'false'], true)) {
                return $lower === 'true' ? '1' : '0';
            }

            return $trimmed;
        }

        return null;
    }
}
