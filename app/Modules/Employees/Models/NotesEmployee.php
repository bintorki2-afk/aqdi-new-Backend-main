<?php

namespace App\Modules\Employees\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotesEmployee extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'addition_date',
        'notes_by_manger',
    ];

    protected $casts = [
        'addition_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
}
