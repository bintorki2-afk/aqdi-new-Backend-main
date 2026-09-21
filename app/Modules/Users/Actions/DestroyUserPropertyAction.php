<?php

namespace App\Modules\Users\Actions;

use App\Models\ContractUnit;
use App\Models\RealEstate;
use App\Models\UnitsReal;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Schema;

class DestroyUserPropertyAction
{
    /**
     * @return array{ok: true}|array{ok: false, message: string, code: int}
     */
    public function execute(int $userId, int $propertyId): array
    {
        $user = User::query()->find($userId);
        if (! $user) {
            return ['ok' => false, 'message' => trans('api.user_not_found'), 'code' => 404];
        }

        $property = RealEstate::query()
            ->where('user_id', $userId)
            ->whereKey($propertyId)
            ->first();

        if (! $property) {
            return ['ok' => false, 'message' => trans('api.real_estate_not_found'), 'code' => 404];
        }

        if ($this->propertyHasContracts($property)) {
            return ['ok' => false, 'message' => trans('api.property_has_contracts'), 'code' => 422];
        }

        UnitsReal::query()->where('real_estates_units_id', $property->id)->delete();
        $property->delete();

        return ['ok' => true];
    }

    private function propertyHasContracts(RealEstate $property): bool
    {
        if ($property->contracts()->exists()) {
            return true;
        }

        if (Schema::hasTable('contract_units') && ContractUnit::query()->where('real_estate_id', $property->id)->exists()) {
            return true;
        }

        return UnitsReal::query()
            ->where('real_estates_units_id', $property->id)
            ->where(function ($q) {
                $q->whereHas('contracts')
                    ->orWhereHas('linkedContracts');
            })
            ->exists();
    }
}
