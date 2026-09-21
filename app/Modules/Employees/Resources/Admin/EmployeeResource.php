<?php

namespace App\Modules\Employees\Resources\Admin;

use App\Http\Resources\Admin\V2\Api\ReceivedContractResource;
use App\Http\Resources\Admin\V2\Api\RefundableContractResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $effective = $this->effectivePermissions();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'base_salary' => $this->base_salary,
            'role_id' => $this->role_id,
            'role' => $this->resolvedRoleName(),
            'role_title' => $this->resolvedRoleTitle(),
            'role_data' => $this->when(
                $this->roleRelation,
                fn () => new RoleBriefResource($this->roleRelation)
            ),
            'is_system_admin' => $this->isSystemAdmin(),
            'has_all_permissions' => $this->hasAllPermissions(),
            'permissions' => $effective['names'],
            'permission_names' => $effective['names'],
            'permission_matrix' => $effective['matrix'],
            ...$this->workPeriodPayload(),
            'is_active' => (bool) $this->is_active,
            'is_online' => (bool) $this->is_online,
            'is_blocked' => $this->blocked_until ? now()->lessThan($this->blocked_until) : false,
            'blocked_until' => $this->blocked_until?->format('Y-m-d H:i:s'),
            'reason_of_block' => $this->reason_of_block,
            'profile_image' => $this->profile_image ? url($this->profile_image) : null,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'whatsapp' => $this->whatsapp,
            'snapchat' => $this->snapchat,
            'tiktok' => $this->tiktok,
            'twitter' => $this->twitter,
            'salaries_count' => $this->salaries_count ?? $this->salaries?->count() ?? 0,
            'salaries' => SalaryResource::collection($this->whenLoaded('salaries')),
            'notes_count' => $this->notes_count ?? $this->notes?->count() ?? 0,
            'notes' => EmployeeNoteResource::collection($this->whenLoaded('notes')),
            'received_contracts_count' => $this->received_contract_count ?? $this->receivedContract?->count() ?? 0,
            'received_contracts' => ReceivedContractResource::collection(
                $this->whenLoaded('receivedContract')
            ),
            'refundable_contracts_count' => $this->refundable_contract_count ?? $this->refundableContract?->count() ?? 0,
            'refundable_contracts' => RefundableContractResource::collection(
                $this->whenLoaded('refundableContract')
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
