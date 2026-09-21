<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Models\Employee;
use App\Services\Admin\RolePermissionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAuthResource extends JsonResource
{
    /**
     * @param  array{
     *     token: string,
     *     refresh_token: string,
     *     token_expires_in: int,
     *     token_expires_at: string,
     *     refresh_token_expires_at: string
     * }|null  $tokens
     */
    public function __construct(Employee $employee, protected ?array $tokens = null)
    {
        parent::__construct($employee);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Employee $employee */
        $employee = $this->resource;
        $employee->loadMissing('roleRelation.permissions');

        $resolver = app(RolePermissionResolver::class);
        $effective = $resolver->effectivePermissionsFor($employee);

        $payload = [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'base_salary' => $employee->base_salary,
            'role_id' => $employee->role_id,
            'role' => $employee->resolvedRoleName(),
            'role_title' => $employee->resolvedRoleTitle(),
            'is_system_admin' => $employee->isSystemAdmin(),
            'has_all_permissions' => $employee->hasAllPermissions(),
            'permissions' => $effective['names'],
            'permission_names' => $effective['names'],
            'permission_matrix' => $effective['matrix'],
            'permission_modules' => $resolver->modulesForEmployee($employee),
            ...$employee->workPeriodPayload(),
            'is_active' => (bool) $employee->is_active,
            'is_online' => (bool) $employee->is_online,
            'is_blocked' => $employee->blocked_until
                ? now()->lessThan($employee->blocked_until)
                : false,
            'blocked_until' => $employee->blocked_until?->format('Y-m-d H:i:s'),
            'reason_of_block' => $employee->reason_of_block,
            'profile_image' => $employee->profile_image
                ? url($employee->profile_image)
                : null,
            'facebook' => $employee->facebook,
            'instagram' => $employee->instagram,
            'whatsapp' => $employee->whatsapp,
            'snapchat' => $employee->snapchat,
            'tiktok' => $employee->tiktok,
            'twitter' => $employee->twitter,
            'fcm_token' => $employee->fcm_token,
        ];

        if ($this->tokens === null) {
            return $payload;
        }

        return array_merge($payload, [
            'token' => $this->tokens['token'],
            'refresh_token' => $this->tokens['refresh_token'],
            'token_expires_in' => $this->tokens['token_expires_in'],
            'token_expires_at' => $this->tokens['token_expires_at'],
            'refresh_token_expires_at' => $this->tokens['refresh_token_expires_at'],
            'token_type' => 'Bearer',
        ]);
    }
}
