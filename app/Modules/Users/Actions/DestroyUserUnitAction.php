<?php

namespace App\Modules\Users\Actions;

use App\Models\UnitsReal;
use App\Modules\Users\Models\User;

class DestroyUserUnitAction
{
    /**
     * @return array{ok: true}|array{ok: false, message: string, code: int}
     */
    public function execute(int $userId, int $unitId): array
    {
        $user = User::query()->find($userId);
        if (! $user) {
            return ['ok' => false, 'message' => trans('api.user_not_found'), 'code' => 404];
        }

        $unit = UnitsReal::query()
            ->whereKey($unitId)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('realEstate', fn ($rq) => $rq->where('user_id', $userId));
            })
            ->first();

        if (! $unit) {
            return ['ok' => false, 'message' => trans('api.unit_not_found'), 'code' => 404];
        }

        if ($this->unitHasContracts($unit)) {
            return ['ok' => false, 'message' => trans('api.unit_has_contracts'), 'code' => 422];
        }

        $unit->delete();

        return ['ok' => true];
    }

    private function unitHasContracts(UnitsReal $unit): bool
    {
        if ($unit->contracts()->exists()) {
            return true;
        }

        return $unit->linkedContracts()->exists();
    }
}
