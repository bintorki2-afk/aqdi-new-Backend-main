<?php

namespace App\Services\Seo\SearchConsole;

use Illuminate\Support\Carbon;

final class SearchConsolePeriod
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {}

    public static function fromInput(?string $from, ?string $to, int $defaultDays = 28): self
    {
        $end = filled($to) ? Carbon::parse($to)->startOfDay() : now()->subDay()->startOfDay();
        $start = filled($from)
            ? Carbon::parse($from)->startOfDay()
            : $end->copy()->subDays(max($defaultDays - 1, 0));

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return new self($start->toDateString(), $end->toDateString());
    }
}
