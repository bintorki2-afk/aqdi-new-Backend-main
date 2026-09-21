<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Api\V2\Contract\Concerns\MapsContractStatusFields;
use App\Support\HijriDobParts;
use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step4Resource extends JsonResource
{
    use MapsContractStatusFields;
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        $tenantDob = HijriDobParts::split($this->tenant_dob);
        $tenantAgentDob = HijriDobParts::split($this->dob_of_property_tenant_agent);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_entity' => $this->tenant_entity,
            'tenant_id_num' => $this->tenant_id_num,
            'tenant_dob' => $this->tenant_dob,
            'tenant_dob_day' => $tenantDob['day'],
            'tenant_dob_month' => $tenantDob['month'],
            'tenant_dob_year' => $tenantDob['year'],
            'type_tenant_dob' => $this->type_tenant_dob ?? 'hijri',
            'type_dob_tenant_agent' => $this->type_dob_tenant_agent ?? 'hijri',
            'tenant_mobile' => $this->tenant_mobile,
            'region_of_the_tenant_legal_agent' => $this->region_of_the_tenant_legal_agent,
            'city_of_the_tenant_legal_agent' => $this->city_of_the_tenant_legal_agent,
            'tenant_entity_unified_registry_number' => $this->tenant_entity_unified_registry_number,
            'authorization_type' => $this->authorization_type,
            'copy_of_the_owner_record' => $this->copy_of_the_owner_record,
            'id_num_of_property_tenant_agent' => $this->id_num_of_property_tenant_agent,
            'mobile_of_property_tenant_agent' => $this->mobile_of_property_tenant_agent,
            'dob_of_property_tenant_agent' => $this->dob_of_property_tenant_agent,
            'dob_of_property_tenant_agent_day' => $tenantAgentDob['day'],
            'dob_of_property_tenant_agent_month' => $tenantAgentDob['month'],
            'dob_of_property_tenant_agent_year' => $tenantAgentDob['year'],
            ...$this->contractStatusFields(),
            'step' => $this->step,
        ];
    }
}

