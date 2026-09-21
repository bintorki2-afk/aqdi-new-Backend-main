<?php

namespace Tests\Unit\Seo;

use App\Services\Seo\SearchConsole\SearchConsolePeriod;
use Carbon\Carbon;
use Tests\TestCase;

class SearchConsolePeriodTest extends TestCase
{
    public function test_it_uses_the_given_dates(): void
    {
        $period = SearchConsolePeriod::fromInput('2026-08-01', '2026-08-28');

        $this->assertSame('2026-08-01', $period->from);
        $this->assertSame('2026-08-28', $period->to);
    }

    public function test_it_defaults_to_the_last_28_days_ending_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 10:00:00'));

        $period = SearchConsolePeriod::fromInput(null, null);

        $this->assertSame('2026-08-03', $period->from);
        $this->assertSame('2026-08-30', $period->to);

        Carbon::setTestNow();
    }
}
