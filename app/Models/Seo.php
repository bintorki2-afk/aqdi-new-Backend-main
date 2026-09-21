<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Seo extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'seos';

     
    protected $fillable = [
        'name',
        'mobile',
        'password',
        'email',
        'email_verified_at',
        'verification_code',
        'is_seo',  
    ];

     protected $hidden = [
        'password', 
        'remember_token',
    ];

     public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }
}
