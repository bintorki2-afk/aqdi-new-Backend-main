<?php

namespace App\Support;

final class DateInputNormalizer
{
    /**
     * Normalize user input to YYYY-MM-DD for MySQL DATE columns.
     * Accepts DD-MM-YYYY, DD/MM/YYYY, or YYYY-MM-DD.
     */
    public static function toMysqlDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = trim($value);
        if ($v === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    /** Build MySQL DATE (YYYY-MM-DD) from day, month, year (Gregorian). */
    public static function combineFromParts(string|int|null $day, string|int|null $month, string|int|null $year): ?string
    {
        if ($day === null || $day === '' || $month === null || $month === '' || $year === null || $year === '') {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            (int) preg_replace('/\D/', '', (string) $year),
            (int) $month,
            (int) $day
        );
    }

    /**
     * Split YYYY-MM-DD (or Carbon) into UI parts for dropdowns.
     *
     * @return array{day: ?string, month: ?string, year: ?string}
     */
    public static function splitMysqlDate(mixed $value): array
    {
        if ($value === null || $value === '') {
            return ['day' => null, 'month' => null, 'year' => null];
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $v = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v, $m)) {
            return [
                'day' => str_pad((string) (int) $m[3], 2, '0', STR_PAD_LEFT),
                'month' => str_pad((string) (int) $m[2], 2, '0', STR_PAD_LEFT),
                'year' => (string) (int) $m[1],
            ];
        }

        return ['day' => null, 'month' => null, 'year' => null];
    }
}
