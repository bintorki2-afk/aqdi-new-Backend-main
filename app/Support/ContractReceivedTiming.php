<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\ReceivedContract;
use Illuminate\Support\Carbon;

class ContractReceivedTiming
{
    /**
     * Admin order-detail metrics:
     * - مستلم منذ = time since the employee received the contract
     * - سرعة الاستلام = time from contract creation until it was received
     *
     * @return array{
     *     is_received: bool,
     *     received_at: string|null,
     *     received_since: string|null,
     *     received_since_label_ar: string,
     *     received_since_minutes: int|null,
     *     receive_speed: string|null,
     *     receive_speed_label_ar: string,
     *     receive_speed_minutes: int|null
     * }
     */
    public static function for(Contract $contract, ?ReceivedContract $received): array
    {
        $receivedAt = self::receivedAt($received);

        if ($received === null || $receivedAt === null) {
            $createdAt = $contract->created_at
                ? Carbon::parse($contract->created_at)
                : null;
            $waitingMinutes = $createdAt !== null
                ? self::minutesBetween($createdAt, Carbon::now())
                : null;

            return [
                'is_received' => false,
                'received_at' => null,
                'received_since' => null,
                'received_since_label_ar' => 'مستلم منذ',
                'received_since_minutes' => null,
                'receive_speed' => null,
                'receive_speed_label_ar' => 'سرعة الاستلام',
                'receive_speed_minutes' => null,
                'waiting_minutes' => $waitingMinutes,
                'waiting_since' => $waitingMinutes === null ? null : 'منذ '.self::durationPhrase($waitingMinutes),
                'exceeded_15_minutes' => $waitingMinutes !== null && $waitingMinutes > 15,
                'exceeded_30_minutes' => $waitingMinutes !== null && $waitingMinutes > 30,
            ];
        }

        $now = Carbon::now();
        $sinceMinutes = self::minutesBetween($receivedAt, $now);
        $createdAt = $contract->created_at
            ? Carbon::parse($contract->created_at)
            : null;
        $speedMinutes = $createdAt !== null
            ? self::minutesBetween($createdAt, $receivedAt)
            : null;

        return [
            'is_received' => true,
            'received_at' => $receivedAt->format('Y-m-d H:i:s'),
            'received_since' => 'منذ '.self::durationPhrase($sinceMinutes),
            'received_since_label_ar' => 'مستلم منذ',
            'received_since_minutes' => $sinceMinutes,
            'receive_speed' => $speedMinutes === null ? null : 'خلال '.self::durationPhrase($speedMinutes),
            'receive_speed_label_ar' => 'سرعة الاستلام',
            'receive_speed_minutes' => $speedMinutes,
            'waiting_minutes' => null,
            'waiting_since' => null,
            'exceeded_15_minutes' => false,
            'exceeded_30_minutes' => false,
        ];
    }

    public static function receivedAt(?ReceivedContract $received): ?Carbon
    {
        if ($received === null) {
            return null;
        }

        if ($received->created_at) {
            return Carbon::parse($received->created_at);
        }

        if ($received->date_of_received) {
            return Carbon::parse($received->date_of_received)->startOfDay();
        }

        return null;
    }

    public static function minutesBetween(Carbon $from, Carbon $to): int
    {
        $seconds = $to->getTimestamp() - $from->getTimestamp();

        return max(0, (int) floor($seconds / 60));
    }

    /**
     * Minutes that fall inside the daily shift window (د عمل).
     *
     * @param  array{start: string, end: string}  $shift
     */
    public static function workMinutesBetween(Carbon $from, Carbon $to, array $shift): int
    {
        if ($to->lte($from)) {
            return 0;
        }

        $startClock = $shift['start'] ?? '09:00';
        $endClock = $shift['end'] ?? '17:00';
        $total = 0;
        $day = $from->copy()->startOfDay();
        $lastDay = $to->copy()->startOfDay();

        while ($day->lte($lastDay)) {
            $shiftStart = self::clockOnDay($day, $startClock);
            $shiftEnd = self::clockOnDay($day, $endClock);

            if ($startClock <= $endClock) {
                $windows = [[$shiftStart, $shiftEnd]];
            } else {
                $windows = [
                    [$day->copy()->startOfDay(), $shiftEnd],
                    [$shiftStart, $day->copy()->endOfDay()],
                ];
            }

            foreach ($windows as [$windowStart, $windowEnd]) {
                $overlapStart = $from->greaterThan($windowStart) ? $from : $windowStart;
                $overlapEnd = $to->lessThan($windowEnd) ? $to : $windowEnd;
                if ($overlapEnd->gt($overlapStart)) {
                    $total += self::minutesBetween($overlapStart, $overlapEnd);
                }
            }

            $day->addDay();
        }

        return $total;
    }

    private static function clockOnDay(Carbon $day, string $clock): Carbon
    {
        try {
            $parsed = Carbon::createFromFormat('H:i', $clock);
        } catch (\Throwable) {
            return $day->copy()->startOfDay();
        }

        return $day->copy()->setTime((int) $parsed->format('H'), (int) $parsed->format('i'), 0);
    }

    /**
     * Compact Arabic duration for KPI cards, e.g. 80 → "1 س 20 د".
     */
    public static function compactDurationPhrase(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        if ($minutes < 1) {
            return '0 د';
        }

        $hours = intdiv($minutes, 60);
        $remain = $minutes % 60;

        if ($hours === 0) {
            return $remain.' د';
        }

        if ($remain === 0) {
            return $hours.' س';
        }

        return $hours.' س '.$remain.' د';
    }

    public static function durationPhrase(int $minutes): string
    {
        if ($minutes < 1) {
            return 'أقل من دقيقة';
        }

        if ($minutes < 60) {
            return self::arabicCount($minutes, 'دقيقة', 'دقيقتين', 'دقائق');
        }

        $hours = intdiv($minutes, 60);
        $remainMinutes = $minutes % 60;

        if ($hours < 24) {
            $hoursLabel = self::arabicCount($hours, 'ساعة', 'ساعتين', 'ساعات');
            if ($remainMinutes === 0) {
                return $hoursLabel;
            }

            return $hoursLabel.' و '.self::arabicCount($remainMinutes, 'دقيقة', 'دقيقتين', 'دقائق');
        }

        $days = intdiv($hours, 24);
        $remainHours = $hours % 24;
        $daysLabel = self::arabicCount($days, 'يوم', 'يومين', 'أيام');

        if ($remainHours === 0) {
            return $daysLabel;
        }

        return $daysLabel.' و '.self::arabicCount($remainHours, 'ساعة', 'ساعتين', 'ساعات');
    }

    private static function arabicCount(int $n, string $singular, string $dual, string $plural): string
    {
        if ($n === 1) {
            return $singular;
        }

        if ($n === 2) {
            return $dual;
        }

        if ($n >= 3 && $n <= 10) {
            return $n.' '.$plural;
        }

        return $n.' '.$singular;
    }
}
