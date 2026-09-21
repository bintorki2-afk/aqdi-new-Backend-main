<?php

namespace App\Enums;

enum ReceivedContractStatus: string
{
    case Pending = 'pending';
    case Finish = 'finish';

    /**
     * Resolve API / UI values (Arabic labels, English synonyms) to a case.
     * Backing values `pending` and `finish` are accepted as-is via tryFrom.
     */
    public static function tryFromFlexible(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $direct = self::tryFrom($value);
        if ($direct !== null) {
            return $direct;
        }

        $lower = strtolower($value);

        return match (true) {
            in_array($value, ['مستلم', 'تم الاستلام', 'تم استلام العقد', 'تم'], true) => self::Finish,
            in_array($lower, ['finish', 'finished', 'completed', 'complete', 'received', 'done'], true) => self::Finish,
            in_array($value, ['غير مستلم', 'قيد الانتظار', 'لم يُستلم', 'لم يستلم', 'في الانتظار'], true) => self::Pending,
            in_array($lower, ['pending', 'waiting', 'not_received', 'not received'], true) => self::Pending,
            default => null,
        };
    }
}
