<?php

namespace Tests\Unit;

use App\Services\Admin\EmployeeKpiService;
use App\Support\ContractReceivedTiming;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class EmployeeKpiPeriodFilterTest extends TestCase
{
    public function test_custom_date_range_is_inclusive(): void
    {
        $filter = (new EmployeeKpiService)->resolvePeriodFilter(
            Request::create('/api/admin/employees/kpis', 'GET', [
                'period' => 'custom',
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-18',
            ])
        );

        $this->assertSame('custom', $filter['key']);
        $this->assertSame('مدة محددة', $filter['label_ar']);
        $this->assertSame('2026-08-01', $filter['date_from']);
        $this->assertSame('2026-08-18', $filter['date_to']);
        $this->assertSame('2026-08-01 00:00:00', $filter['range'][0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-18 23:59:59', $filter['range'][1]->format('Y-m-d H:i:s'));
    }

    public function test_date_params_win_over_named_period(): void
    {
        $filter = (new EmployeeKpiService)->resolvePeriodFilter(
            Request::create('/api/admin/employees/kpis', 'GET', [
                'period' => 'today',
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ])
        );

        $this->assertSame('custom', $filter['key']);
        $this->assertSame('2026-07-01', $filter['date_from']);
        $this->assertSame('2026-07-31', $filter['date_to']);
    }

    public function test_custom_period_without_dates_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EmployeeKpiService)->resolvePeriodFilter(
            Request::create('/api/admin/employees/kpis', 'GET', [
                'period' => 'custom',
            ])
        );
    }

    public function test_one_sided_date_range_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EmployeeKpiService)->resolvePeriodFilter(
            Request::create('/api/admin/employees/kpis', 'GET', [
                'date_from' => '2026-08-01',
            ])
        );
    }

    public function test_summarize_rolls_up_cards_and_averages(): void
    {
        $summary = (new EmployeeKpiService)->summarize([
            [
                'cards' => [
                    ['key' => 'received', 'value' => 10],
                    ['key' => 'completed', 'value' => 3],
                    ['key' => 'late_over_24h', 'value' => 2],
                    ['key' => 'assigned', 'value' => 10],
                    ['key' => 'returned', 'value' => 1],
                ],
                'avg_receive' => ['value' => 10],
                'avg_process' => ['value' => 80],
                'revenue' => ['value' => 1000],
            ],
            [
                'cards' => [
                    ['key' => 'received', 'value' => 23],
                    ['key' => 'completed', 'value' => 5],
                    ['key' => 'late_over_24h', 'value' => 23],
                    ['key' => 'assigned', 'value' => 23],
                    ['key' => 'returned', 'value' => 0],
                ],
                'avg_receive' => ['value' => 20],
                'avg_process' => ['value' => null],
                'revenue' => ['value' => 2400.5],
            ],
        ]);

        $this->assertSame(2, $summary['employees_count']);
        $this->assertSame(33, $summary['received_total']);
        $this->assertSame(8, $summary['completed_total']);
        $this->assertSame(25, $summary['late_over_24h_total']);
        $this->assertSame(33, $summary['assigned_total']);
        $this->assertSame(1, $summary['returned_total']);
        $this->assertSame(15.0, $summary['avg_receive_work_minutes']);
        $this->assertSame(80.0, $summary['avg_process_minutes']);
        $this->assertSame(3400.5, $summary['revenue_sar_total']);
    }

    public function test_compact_duration_phrase(): void
    {
        $this->assertSame('—', ContractReceivedTiming::compactDurationPhrase(null));
        $this->assertSame('0 د', ContractReceivedTiming::compactDurationPhrase(0));
        $this->assertSame('5 د', ContractReceivedTiming::compactDurationPhrase(5));
        $this->assertSame('1 س', ContractReceivedTiming::compactDurationPhrase(60));
        $this->assertSame('1 س 20 د', ContractReceivedTiming::compactDurationPhrase(80));
    }
}
