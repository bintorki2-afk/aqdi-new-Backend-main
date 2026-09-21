<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthHistory extends Model
{
     protected $fillable = [
        'user_id',
        'employee_id',
        'login_at',
        'logout_at',
     ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }   
    
     public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }    
    
}