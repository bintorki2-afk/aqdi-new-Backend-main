<?php

namespace App\Support\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Shared "today | yesterday | last_7_days | last_30_days | all | custom" period
 * resolution for admin reports endpoints (query params: period, date_from, date_to).
 */
trait ResolvesReportPeriod
{
    public const REPORT_PERIODS = [
        'today' => 'اليوم',
        'yesterday' => 'أمس',
        'last_7_days' => 'آخر 7 أيام',
        'last_30_days' => 'آخر 30 يومًا',
        'all' => 'كل الفترات',
        'custom' => 'مدة محددة',
    ];

    public function normalizeReportPeriod(?string $raw): string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return 'today';
        }

        $aliases = [
            'today' => 'today',
            'اليوم' => 'today',
            'yesterday' => 'yesterday',
            'أمس' => 'yesterday',
            'last_7_days' => 'last_7_days',
            '7d' => 'last_7_days',
            'week' => 'last_7_days',
            'last_30_days' => 'last_30_days',
            '30d' => 'last_30_days',
            'month' => 'last_30_days',
            'all' => 'all',
            'custom' => 'custom',
        ];

        $normalized = mb_strtolower($value);

        return $aliases[$value] ?? $aliases[$normalized] ?? 'today';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function reportPeriodRange(string $period): ?array
    {
        $period = $this->normalizeReportPeriod($period);
        $now = now();

        return match ($period) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'all', 'custom' => null,
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * @return array{key: string, label_ar: string, range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null}
     */
    public function resolveReportPeriodFilter(Request $request): array
    {
        $dateFromRaw = $this->reportQueryString($request, 'date_from');
        $dateToRaw = $this->reportQueryString($request, 'date_to');
        $hasFrom = $dateFromRaw !== null;
        $hasTo = $dateToRaw !== null;

        if ($hasFrom xor $hasTo) {
            throw new InvalidArgumentException('يجب تحديد date_from و date_to معاً.');
        }

        $period = $this->normalizeReportPeriod($this->reportQueryString($request, 'period'));

        if ($hasFrom && $hasTo) {
            $from = $this->parseReportDateBoundary($dateFromRaw, false);
            $to = $this->parseReportDateBoundary($dateToRaw, true);
            if ($from->gt($to)) {
                throw new InvalidArgumentException('date_from يجب أن يكون قبل أو يساوي date_to.');
            }

            return [
                'key' => 'custom',
                'label_ar' => self::REPORT_PERIODS['custom'],
                'range' => [$from, $to],
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ];
        }

        if ($period === 'custom') {
            throw new InvalidArgumentException('يجب تحديد date_from و date_to عند اختيار مدة محددة.');
        }

        return $this->reportFilterFromPeriodKey($period);
    }

    /**
     * @return array{key: string, label_ar: string, range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null}
     */
    public function reportFilterFromPeriodKey(string $period): array
    {
        $period = $this->normalizeReportPeriod($period);
        if ($period === 'custom') {
            $period = 'today';
        }

        $range = $this->reportPeriodRange($period);

        return [
            'key' => $period,
            'label_ar' => self::REPORT_PERIODS[$period],
            'range' => $range,
            'date_from' => $range === null ? null : $range[0]->toDateString(),
            'date_to' => $range === null ? null : $range[1]->toDateString(),
        ];
    }

    /**
     * @return list<array{key: string, label_ar: string, selected: bool}>
     */
    public function reportPeriodTabs(string $selected): array
    {
        $selected = $this->normalizeReportPeriod($selected);

        return collect(self::REPORT_PERIODS)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label_ar' => $label,
                'selected' => $key === $selected,
            ])
            ->values()
            ->all();
    }

    private function reportQueryString(Request $request, string $key): ?string
    {
        $raw = $request->input($key);
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }

    private function parseReportDateBoundary(string $value, bool $endOfDay): Carbon
    {
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $date = Carbon::createFromFormat('Y-m-d', $value);
                if (! $date instanceof Carbon || $date->format('Y-m-d') !== $value) {
                    throw new InvalidArgumentException('صيغة التاريخ يجب أن تكون YYYY-MM-DD.');
                }
            } else {
                $date = Carbon::parse($value);
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new InvalidArgumentException('صيغة التاريخ يجب أن تكون YYYY-MM-DD.');
        }

        if (! $date instanceof Carbon) {
            throw new InvalidArgumentException('صيغة التاريخ يجب أن تكون YYYY-MM-DD.');
        }

        return $endOfDay ? $date->copy()->endOfDay() : $date->copy()->startOfDay();
    }
}
