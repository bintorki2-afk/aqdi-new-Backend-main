<?php

namespace App\Modules\Employees\Models;

use App\Models\AuthHistory;
use App\Models\ContractPaidByEmployee;
use App\Models\ReceivedContract;
use App\Models\RefundableContract;
use App\Modules\Auth\Models\EmployeeRefreshToken;
use App\Services\Admin\RolePermissionResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'base_salary',
        'phone',
        'is_active',
        'is_online',
        'email',
        'profile_image',
        'facebook',
        'instagram',
        'whatsapp',
        'snapchat',
        'tiktok',
        'twitter',
        'blocked_until',
        'password',
        'role',
        'role_id',
        'work_period',
        'reason_of_block',
        'fcm_token',

    ];

    protected $hidden = ['password'];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'base_salary' => 'decimal:2',
    ];

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function notes()
    {
        return $this->hasMany(NotesEmployee::class);
    }

    public function receivedContract()
    {
        return $this->hasMany(ReceivedContract::class);
    }

    public function refundableContract()
    {
        return $this->hasMany(RefundableContract::class);
    }

    public function contractPaidByEmployees()
    {
        return $this->hasMany(ContractPaidByEmployee::class, 'employee_id');
    }

    public function authHistory()
    {
        return $this->hasMany(AuthHistory::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(EmployeeRefreshToken::class);
    }

    /**
     * Relationship: Employee belongs to a role
     */
    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Role slug from `roles.name` (falls back to legacy `employees.role` text).
     */
    public function resolvedRoleName(): ?string
    {
        if ($this->roleRelation) {
            return $this->roleRelation->name;
        }

        $legacy = $this->getRawOriginal('role');

        return is_string($legacy) && $legacy !== '' ? $legacy : null;
    }

    /**
     * Role display title from `roles` (falls back to legacy text column).
     */
    public function resolvedRoleTitle(): ?string
    {
        if ($this->roleRelation) {
            return $this->roleRelation->title_trans
                ?? $this->roleRelation->title_ar
                ?? $this->roleRelation->name;
        }

        return $this->resolvedRoleName();
    }

    /**
     * Whether this employee's role has the given "section.action" permission
     * (e.g. "analytics.view").
     *
     * Admin / system-admin employees always return true (full project access),
     * even when `role_permissions` is empty. Regular employees are limited to
     * the permissions attached to their role.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }

        if (! $this->roleRelation) {
            return false;
        }

        $this->loadMissing('roleRelation.permissions');

        return $this->roleRelation->permissions
            ->where('is_active', true)
            ->contains('name', $permissionName);
    }

    /**
     * Employee from `employees` whose role is Admin gets the whole dashboard
     * without needing assigned permissions. Everyone else uses the matrix.
     */
    public function isSystemAdmin(): bool
    {
        $this->loadMissing('roleRelation');

        if ($this->roleRelation?->isFullAccess()) {
            return true;
        }

        if (Role::grantsFullAccess(
            $this->resolvedRoleName(),
            $this->roleRelation?->title_en,
            $this->roleRelation?->title_ar
        )) {
            return true;
        }

        $legacy = $this->getRawOriginal('role');

        return is_string($legacy) && Role::grantsFullAccess($legacy);
    }

    public function hasAllPermissions(): bool
    {
        return $this->isSystemAdmin();
    }

    /**
     * @return array{names: array<int, string>, matrix: array<string, array<int, string>>}
     */
    public function effectivePermissions(): array
    {
        return app(RolePermissionResolver::class)->effectivePermissionsFor($this);
    }

    /**
     * Client-selected work window: morning (صباحا) or evening (مساء).
     */
    public function resolvedWorkPeriod(): string
    {
        return self::normalizeWorkPeriod($this->work_period) ?? 'morning';
    }

    /**
     * @return array<string, mixed>
     */
    public function workPeriodPayload(): array
    {
        $period = $this->resolvedWorkPeriod();
        $config = (array) (config("employee_kpis.work_periods.{$period}") ?: config('employee_kpis.work_periods.morning'));

        return [
            'work_period' => $period,
            'work_period_label' => app()->getLocale() === 'en'
                ? ($config['label_en'] ?? $period)
                : ($config['label_ar'] ?? $period),
            'work_period_label_ar' => $config['label_ar'] ?? $period,
            'work_period_label_en' => $config['label_en'] ?? $period,
            'shift' => [
                'name' => $config['shift_name'] ?? $period,
                'start' => $config['start'] ?? '09:00',
                'end' => $config['end'] ?? '17:00',
            ],
        ];
    }

    public static function normalizeWorkPeriod(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        return match ($normalized) {
            'morning', 'am', 'صباحا', 'صباح', 'morning_shift' => 'morning',
            'evening', 'pm', 'مساء', 'evening_shift' => 'evening',
            default => null,
        };
    }
}
