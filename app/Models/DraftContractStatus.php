<?php

namespace App\Models;

use App\Support\ContractStatusCase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftContractStatus extends Model
{
    use HasFactory;

    /** Default draft status name for newly created drafts. */
    public const NEW_NAME = 'جديد';

    public const RETURN_ID = 2;

    public const WHATSAPP_DRAFT_ID = 8;

    public const EJAR_AUTHENTICATION_ID = 9;

    public const WAITING_SUPERVISOR_ID = 10;

    protected $fillable = [
        'name',
        'color',
        'color_text',
        'description',
        'client_explanation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['created_at_label', 'status_case'];

    public function getCreatedAtLabelAttribute()
    {
        return $this->created_at
            ? date('Y-m-d H:i A', strtotime($this->created_at))
            : null;
    }

    /**
     * Extra fields the admin UI must collect when changing a draft to this status.
     *
     * @return array{key: string, fields: list<array<string, mixed>>}|null
     */
    public function getStatusCaseAttribute(): ?array
    {
        return ContractStatusCase::schemaFor((int) $this->id, $this->name);
    }

    public static function newStatusId(): ?int
    {
        $id = static::query()->where('name', self::NEW_NAME)->value('id');

        return $id !== null ? (int) $id : null;
    }
}
