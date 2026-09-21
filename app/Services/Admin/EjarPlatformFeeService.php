<?php

namespace App\Services\Admin;

use App\Models\Contract;
use App\Models\Payment;
use App\Support\EjarPlatformFee;
use Illuminate\Support\Carbon;

class EjarPlatformFeeService
{
    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function totalForPeriod(?array $range): float
    {
        $contracts = $this->paidContracts($range);

        if ($contracts->isEmpty()) {
            return 0.0;
        }

        return round((float) $contracts->sum(fn (Contract $contract) => EjarPlatformFee::forContract($contract)), 2);
    }

    /**
     * Unique non-deleted contracts that have a successful payment in the period.
     *
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return \Illuminate\Support\Collection<int, Contract>
     */
    public function paidContracts(?array $range)
    {
        $payments = Payment::query()->successful();
        $this->applyPaymentDateRange($payments, $range);

        $uuids = $payments
            ->pluck('contract_uuid')
            ->map(fn ($uuid) => (string) preg_replace('/-.*$/', '', (string) $uuid))
            ->filter()
            ->unique()
            ->values();

        if ($uuids->isEmpty()) {
            return collect();
        }

        return Contract::query()
            ->notDeleted()
            ->whereIn('uuid', $uuids)
            ->with('contractTermInYears')
            ->get();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function applyPaymentDateRange($query, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        $query->whereBetween('payment_date', [$range[0]->toDateString(), $range[1]->toDateString()]);
    }
}
