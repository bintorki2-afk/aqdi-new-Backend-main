<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;

class Admin extends Authenticatable 
{
    use HasFactory , Notifiable; 

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'photo',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['photo_path', 'role_label', 'created_at_label'];

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    public function getPhotoPathAttribute()
    {
        return isset($this->photo) ? getFilePath($this->photo) : asset('panel/assets/images/users/user-avatar.png');
    }

    public function getRoleLabelAttribute()
    {
        return boolval($this->is_admin) ? 'أدمن' : 'مشرف';
    }

    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }


     
 }
