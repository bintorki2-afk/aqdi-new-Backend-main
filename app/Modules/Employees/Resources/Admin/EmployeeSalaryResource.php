<?php

namespace App\Modules\Employees\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSalaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'base_salary' => $this->base_salary,
            'salaries_count' => $this->salaries_count ?? $this->salaries?->count() ?? 0,
            'salaries' => SalaryResource::collection($this->whenLoaded('salaries')),
        ];
    }
}
