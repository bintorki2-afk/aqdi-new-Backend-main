<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Models\Concerns\HasCreatedAtLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasCreatedAtLabel;
    use HasFactory;

    protected $fillable = [
        'bank_name_ar',
        'bank_name_en',
        'bank_account_name_ar',
        'bank_account_name_en',
        'bank_account_number',
        'iban_number',
    ];

    protected $appends = ['created_at_label', 'bank_name_trans', 'bank_account_name_trans'];

    public function getBankNameTransAttribute()
    {
        return getTransAttribute($this, 'bank_name');
    }

    public function getBankAccountNameTransAttribute()
    {
        return getTransAttribute($this, 'bank_account_name');
    }
}
