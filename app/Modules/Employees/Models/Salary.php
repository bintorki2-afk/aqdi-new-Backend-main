<?php

namespace App\Modules\Employees\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'employee_id',
        'addition_date',
        'due_date',
        'basic_salary',
        'deduction',
        'bonus',
        'total',
        'month',
        'is_paid',
    ];

    protected $casts = [
        'addition_date' => 'date',
        'due_date' => 'date',
        'basic_salary' => 'decimal:2',
        'deduction' => 'decimal:2',
        'bonus' => 'decimal:2',
        'total' => 'decimal:2',
        'is_paid' => 'boolean',
    ];


 
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
