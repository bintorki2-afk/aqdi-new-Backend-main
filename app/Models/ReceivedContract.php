<?php

namespace App\Models;

use App\Enums\ReceivedContractStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'employee_id',
        'status',
        'notes',
        'date_of_received',
    ];

    protected $casts = [
        'date_of_received' => 'date',
        'status' => ReceivedContractStatus::class,
    ];

    public function isFinished(): bool
    {
        return $this->status === ReceivedContractStatus::Finish;
    }

 
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
    
}
