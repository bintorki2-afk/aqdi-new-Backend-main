<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSeoConnection extends Model
{
    public const PROVIDER = 'google';

    protected $fillable = [
        'provider',
        'google_email',
        'google_user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'search_console_site_url',
        'analytics_property_id',
        'connected_by_employee_id',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'connected_by_employee_id');
    }

    public function isConnected(): bool
    {
        return filled($this->refresh_token) || filled($this->access_token);
    }

    public function hasScope(string $needle): bool
    {
        foreach ($this->scopes ?? [] as $scope) {
            if (str_contains((string) $scope, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function hasSearchConsole(): bool
    {
        return $this->isConnected() && $this->hasScope('webmasters');
    }
}
