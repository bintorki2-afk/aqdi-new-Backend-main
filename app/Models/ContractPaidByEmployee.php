<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPaidByEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_uuid',
        'employee_id',
        'customer_mobile',
        'contract_type',
        'contract_period_id',
        'draft_contract_number',
        'draft_contract_id',
        'amount',
        'is_paid',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_uuid', 'uuid');
    }

    public function contractPeriod()
    {
        return $this->belongsTo(ContractPeriod::class, 'contract_period_id');
    }

    public function draftContract()
    {
        return $this->belongsTo(Contract::class, 'draft_contract_id');
    }
}
