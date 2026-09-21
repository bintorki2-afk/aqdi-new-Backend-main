<?php

namespace App\Support;

use InvalidArgumentException;

final class MessageAlertType
{
    public const CLIENT = 'client';

    public const PROPERTY = 'property';

    public const EMPLOYEE = 'employee';

    public const ROUTE_PATTERN = 'client|property|employee';

    /**
     * @return array<string, array{key: string, label_ar: string, label_en: string, show_in_overview?: bool}>
     */
    public static function definitions(): array
    {
        return config('message_alert_types', []);
    }

    /**
     * Types shown as dashboard cards (two tiles in admin UI).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function overviewDefinitions(): array
    {
        return array_filter(
            self::definitions(),
            fn (array $def) => (bool) ($def['show_in_overview'] ?? false)
        );
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return self::*
     */
    public static function normalize(mixed $value): string
    {
        $t = strtolower(trim((string) ($value ?? self::CLIENT)));

        if (! in_array($t, self::allowed(), true)) {
            throw new InvalidArgumentException(
                'type must be one of: '.implode(', ', self::allowed())
            );
        }

        return $t;
    }

    /**
     * @return self::*
     */
    public static function tryNormalize(mixed $value): string
    {
        $t = strtolower(trim((string) ($value ?? self::CLIENT)));

        return in_array($t, self::allowed(), true) ? $t : self::CLIENT;
    }

    public static function labelAr(string $type): ?string
    {
        return self::definitions()[$type]['label_ar'] ?? null;
    }

    public static function labelEn(string $type): ?string
    {
        return self::definitions()[$type]['label_en'] ?? null;
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::allowed());
    }
}
