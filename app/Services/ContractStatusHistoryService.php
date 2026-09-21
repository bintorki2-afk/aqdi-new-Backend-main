<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStatusHistory;
use App\Support\ContractFrontendStatus;

class ContractStatusHistoryService
{
    /**
     * Record a status event. Skips if same as the latest history row.
     *
     * @param  array{
     *     status?: string,
     *     status_label?: string,
     *     status_type?: string,
     *     status_id?: int|null,
     *     status_color?: string|null,
     *     status_description?: string|null,
     *     status_client_explanation?: string|null,
     *     source?: string|null,
     *     meta?: array<string, mixed>|null
     * }  $override
     */
    public function record(Contract $contract, array $override = []): ?ContractStatusHistory
    {
        $payload = array_merge(ContractFrontendStatus::for($contract), $override);

        $status = (string) ($payload['status'] ?? 'unknown');
        $label = (string) ($payload['status_label'] ?? 'غير محدد');
        $type = (string) ($payload['status_type'] ?? 'contract');
        $statusId = isset($payload['status_id']) ? (int) $payload['status_id'] : null;
        $clientExplanation = $payload['status_client_explanation'] ?? null;
        $meta = $override['meta'] ?? null;

        $latest = ContractStatusHistory::query()
            ->where('contract_id', $contract->id)
            ->latest('id')
            ->first();

        if ($latest
            && $latest->status === $status
            && (string) $latest->status_label === $label
            && (int) ($latest->status_id ?? 0) === (int) ($statusId ?? 0)
        ) {
            return $latest;
        }

        return ContractStatusHistory::query()->create([
            'contract_id' => $contract->id,
            'status_type' => $type,
            'status_id' => $statusId,
            'status' => $status,
            'status_label' => $label,
            'status_color' => $payload['status_color'] ?? null,
            'status_description' => $payload['status_description'] ?? null,
            'client_explanation' => $clientExplanation,
            'source' => $override['source'] ?? 'system',
            'meta' => is_array($meta) && $meta !== [] ? $meta : null,
        ]);
    }

    /**
     * Record an explicit system step (e.g. payment) with fixed Arabic label.
     */
    public function recordExplicit(
        Contract $contract,
        string $status,
        string $statusLabel,
        string $source = 'system',
        ?string $color = null,
        ?string $description = null,
        ?string $clientExplanation = null
    ): ?ContractStatusHistory {
        return $this->record($contract, [
            'status' => $status,
            'status_label' => $statusLabel,
            'status_type' => 'system',
            'status_id' => null,
            'status_color' => $color,
            'status_description' => $clientExplanation ?? $description,
            'status_client_explanation' => $clientExplanation ?? $description,
            'source' => $source,
        ]);
    }

    /**
     * Ensure history exists for API: backfill current (+ paid) if empty.
     */
    public function ensureSeeded(Contract $contract): void
    {
        // Use the eager-loaded relation when the caller preloaded it (list endpoints) — avoids
        // two extra queries per contract row.
        $exists = $contract->relationLoaded('statusHistories')
            ? $contract->statusHistories->isNotEmpty()
            : ContractStatusHistory::query()->where('contract_id', $contract->id)->exists();

        if ($exists) {
            return;
        }

        if ((bool) $contract->is_completed) {
            $this->recordExplicit(
                $contract,
                'paid',
                'تم الدفع',
                'payment',
                '#16A34A',
                'تم استلام المقابل المالي',
                'تم استلام المقابل المالي'
            );
        }

        $this->record($contract, ['source' => 'system']);
    }

    /**
     * Dynamic timeline: only statuses that actually happened.
     *
     * @return list<array<string, mixed>>
     */
    public function timeline(Contract $contract): array
    {
        $preloaded = $contract->relationLoaded('statusHistories') && $contract->statusHistories->isNotEmpty();

        $this->ensureSeeded($contract);

        $rows = $preloaded
            ? $contract->statusHistories->sortBy('id')->values()
            : ContractStatusHistory::query()
                ->where('contract_id', $contract->id)
                ->orderBy('id')
                ->get();

        if ($rows->isEmpty()) {
            $payload = ContractFrontendStatus::for($contract);

            return [[
                'status' => $payload['status'],
                'status_label' => $payload['status_label'],
                'status_color' => $payload['status_color'],
                'status_description' => $payload['status_description'],
                'client_explanation' => $payload['status_client_explanation'],
                'status_client_explanation' => $payload['status_client_explanation'],
                'status_type' => $payload['status_type'],
                'status_id' => $payload['status_id'],
                'state' => 'current',
                'source' => 'system',
                'created_at' => optional($contract->updated_at)?->toIso8601String(),
            ]];
        }

        $lastIndex = $rows->count() - 1;

        return $rows->values()->map(function (ContractStatusHistory $row, int $i) use ($lastIndex) {
            $client = $row->client_explanation ?: $row->status_description;

            return [
                'id' => $row->id,
                'status' => $row->status,
                'status_label' => $row->status_label,
                'status_color' => $row->status_color,
                'status_description' => $client,
                'client_explanation' => $row->client_explanation,
                'status_client_explanation' => $row->client_explanation,
                'status_type' => $row->status_type,
                'status_id' => $row->status_id,
                'state' => $i < $lastIndex ? 'completed' : 'current',
                'source' => $row->source,
                'meta' => $row->meta,
                'created_at' => optional($row->created_at)?->toIso8601String(),
            ];
        })->all();
    }
}
