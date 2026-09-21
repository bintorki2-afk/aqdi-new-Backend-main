<?php

namespace App\Modules\Employees\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'addition_date' => $this->addition_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'basic_salary' => $this->basic_salary,
            'deduction' => $this->deduction,
            'bonus' => $this->bonus,
            'total' => $this->total,
            'month' => $this->month,
            'is_paid' => (bool) $this->is_paid,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
