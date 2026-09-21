<?php

namespace App\Modules\Catalog\Support;

final class ContractTypes
{
    public const HOUSING = 'housing';

    public const COMMERCIAL = 'commercial';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::HOUSING, self::COMMERCIAL];
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::all());
    }
}
