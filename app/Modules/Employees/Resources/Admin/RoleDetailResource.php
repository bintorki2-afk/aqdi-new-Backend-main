<?php

namespace App\Modules\Employees\Resources\Admin;

use App\Services\Admin\RolePermissionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $effectivePermissions = app(RolePermissionResolver::class)
            ->effectivePermissionsForRole($this->resource);

        return array_merge(
            (new RoleResource($this))->toArray($request),
            [
                'is_full_access' => $this->resource->isFullAccess(),
                'permissions' => PermissionResource::collection(
                    $this->whenLoaded('permissions')
                ),
                'permission_ids' => $this->when(
                    $this->relationLoaded('permissions'),
                    fn () => $this->permissions->pluck('id')->values()->all()
                ),
                'permission_names' => $effectivePermissions['names'],
                'permission_matrix' => $effectivePermissions['matrix'],
            ]
        );
    }
}
