<?php

namespace App\Shared\Helpers;

class SaudiMobile
{
    /**
     * National Saudi mobile: 5XXXXXXXX (9 digits).
     * Accepts 05… / 966… / 00966… and strips the leading zero / country code.
     */
    public static function toNational(?string $mobile): ?string
    {
        if ($mobile === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($mobile)) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        return $digits !== '' ? $digits : null;
    }

    /**
     * Laravel validation rule fragment for a national Saudi mobile.
     */
    public static function rule(bool $required = false): string
    {
        $prefix = $required ? 'required' : 'nullable';

        return "{$prefix}|min:9|regex:/^5[0-9]{8}$/";
    }
}
