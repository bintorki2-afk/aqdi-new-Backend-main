<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'title_ar',
        'title_en',
        'answer_ar',
        'answer_en',
    ];

    protected $appends = ['created_at_label', 'title_trans', 'answer_trans'];

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }

    public function getTitleTransAttribute()
    {
        return getTransAttribute($this, 'title');
    }

    public function getAnswerTransAttribute()
    {
        return getTransAttribute($this, 'answer');
    }
}
