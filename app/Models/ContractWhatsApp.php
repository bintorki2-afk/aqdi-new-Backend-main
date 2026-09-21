<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractWhatsApp extends Model
{
    use HasFactory;

    protected $table = 'contract_whatsapp';

    protected $fillable = [
        'mobile_number',
        'addition_date',
        'contract_type',
        'without',
        'derived_from_bank',
        'waqf',
        'paper_deed',
        'paper_deed_2',
        'is_documented',
        'contract_duration',
        'amount_paid_by_client',
        'rental_fees',
        'notes',
        'time',
        'date',
        'is_complete',
    ];

    protected $casts = [
        'addition_date' => 'datetime',
        'date' => 'date',
        'time' => 'string',
        'without' => 'boolean',
        'derived_from_bank' => 'boolean',
        'waqf' => 'boolean',
        'paper_deed' => 'boolean',
        'paper_deed_2' => 'boolean',
        'is_documented' => 'boolean',
        'is_complete' => 'boolean',
        'contract_duration' => 'integer',
        'amount_paid_by_client' => 'decimal:2',
        'rental_fees' => 'decimal:2',
    ];

    protected $appends = [
        'contract_type_trans',
    ];

    /**
     * Get the contract period relationship
     */
    public function contractPeriod()
    {
        return $this->belongsTo(ContractPeriod::class, 'contract_duration');
    }

    public function getContractTypeTransAttribute(): ?string
    {
        if ($this->contract_type === null || $this->contract_type === '') {
            return null;
        }

        $locale = app()->getLocale();

        if ($locale === 'en') {
            return match ($this->contract_type) {
                'commercial' => 'Commercial',
                'residential', 'housing' => 'Residential',
                default => $this->contract_type,
            };
        }

        return match ($this->contract_type) {
            'commercial' => 'تجاري',
            'residential', 'housing' => 'سكني',
            default => $this->contract_type,
        };
    }

    /**
     * Human-readable contract duration (from contract_periods.period or note).
     */
    public function getContractDurationNameAttribute(): ?string
    {
        $period = $this->contractPeriod;

        if (! $period) {
            return null;
        }

        if ($period->period !== null && $period->period !== '') {
            return $period->period;
        }

        return $period->note_trans ?: null;
    }
}
