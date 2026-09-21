<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    public const STATUS_POTENTIAL = 'potential';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'name',
        'phone',
        'contract_uuid',
        'contract_id',
        'amount',
        'contract_type',
        'status',
        'source',
        'converted_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'converted_at' => 'datetime',
    ];

    /**
     * Mark every lead for a contract uuid as converted/paid.
     */
    public static function markPaidForContractUuid(?string $contractUuid): void
    {
        if (! filled($contractUuid)) {
            return;
        }

        // Lead conversion is a side effect of payment completion and must NEVER make a
        // payment fail (e.g. code deployed before the `leads` migration ran, or a partial schema).
        try {
            // Match exact uuid or the "-suffix" payment variants used elsewhere.
            static::query()
                ->where('status', self::STATUS_POTENTIAL)
                ->where(function ($q) use ($contractUuid) {
                    $q->where('contract_uuid', $contractUuid)
                        ->orWhere('contract_uuid', 'like', $contractUuid . '-%');
                })
                ->update([
                    'status' => self::STATUS_PAID,
                    'converted_at' => now(),
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lead conversion skipped', [
                'contract_uuid' => $contractUuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
