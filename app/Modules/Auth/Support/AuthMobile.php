<?php

namespace App\Modules\Auth\Support;

final class AuthMobile
{
    public static function normalizeSaudiMobile(string $mobile): string
    {
        if (str_starts_with($mobile, '00966')) {
            $national = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $national = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $national = ltrim($mobile, '0');
        } else {
            $national = $mobile;
        }

        return '00966'.$national;
    }

    /**
     * @return list<string>
     */
    public static function lookupVariants(string $mobile): array
    {
        $formattedMobile = self::normalizeSaudiMobile($mobile);

        if (str_starts_with($mobile, '00966')) {
            $national = substr($mobile, 5);
        } elseif (str_starts_with($mobile, '966')) {
            $national = substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0')) {
            $national = ltrim($mobile, '0');
        } else {
            $national = $mobile;
        }

        return array_values(array_unique(array_filter([
            $mobile,
            $formattedMobile,
            '966'.$national,
            '0'.$national,
            $national,
        ])));
    }
}
