<?php

namespace App\Modules\Settings\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\SettingContractResource;
use App\Http\Traits\Responser;
use App\Models\SettingContract;
use Illuminate\Http\Request;

class SettingContractController extends Controller
{
    use Responser;

    /**
     * GET /api/v2/setting-contracts
     * Optional: ?instrument_type=electronic
     */
    public function index(Request $request)
    {
        $query = SettingContract::query();

        if ($request->filled('instrument_type')) {
            $query->where(
                'instrument_type',
                strtolower(trim($request->string('instrument_type')->toString()))
            );
        }

        $items = $query->orderBy('id')->get();

        return $this->apiResponse(
            SettingContractResource::collection($items),
            trans('api.success')
        );
    }

    /**
     * GET /api/v2/setting-contracts/{id}
     */
    public function show(int $id)
    {
        $setting = SettingContract::query()->find($id);

        if (! $setting) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        return $this->apiResponse(
            new SettingContractResource($setting),
            trans('api.success')
        );
    }
}
