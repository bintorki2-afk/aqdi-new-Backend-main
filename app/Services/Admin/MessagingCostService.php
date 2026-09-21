<?php

namespace App\Services\Admin;

use App\Models\SmsLog;
use Illuminate\Support\Carbon;

class MessagingCostService
{
    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function totalForPeriod(?array $range): float
    {
        $query = SmsLog::query()->whereNotNull('cost')->where('cost', '>', 0);

        if ($range !== null) {
            $query->whereBetween('sent_at', [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()]);
        }

        return round((float) $query->sum('cost'), 2);
    }
}
