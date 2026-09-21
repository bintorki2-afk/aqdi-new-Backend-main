<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantRole extends Model
{
    use HasFactory;

    public const INPUT_TYPE_TEXT = 'text';

    public const INPUT_TYPE_NUMBER = 'number';

    protected $fillable = [
        'text_of_reason',
        'service_definition',
        'input_field_label',
        'input_field_type',
        'icon',
        'input_icon',
        'pop',
    ];

    protected $casts = [
        'pop' => 'boolean',
    ];

    /**
     * @return list<string>
     */
    public static function inputFieldTypes(): array
    {
        return [self::INPUT_TYPE_TEXT, self::INPUT_TYPE_NUMBER];
    }

    public function requiresUserInput(): bool
    {
        return $this->input_field_type !== null
            && $this->input_field_type !== ''
            && $this->input_field_label !== null
            && trim((string) $this->input_field_label) !== '';
    }

    public function shouldPop(): bool
    {
        return (bool) $this->pop;
    }
}
