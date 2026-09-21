<?php

namespace App\Modules\Settings\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Setting;

class MeterFeeSettingController extends Controller
{
    use Responser;

    /**
     * GET /api/v2/meter-fee-settings
     */
    public function show()
    {
        $setting = Setting::query()->first();

        return $this->apiResponse([
            'electricity_meter_fee_commercial_tenant' => $setting?->electricity_meter_fee_commercial_tenant !== null
                ? (float) $setting->electricity_meter_fee_commercial_tenant
                : null,
            'electricity_meter_fee_housing_tenant' => $setting?->electricity_meter_fee_housing_tenant !== null
                ? (float) $setting->electricity_meter_fee_housing_tenant
                : null,
            'water_meter_fee_commercial_tenant' => $setting?->water_meter_fee_commercial_tenant !== null
                ? (float) $setting->water_meter_fee_commercial_tenant
                : null,
            'water_meter_fee_housing_tenant' => $setting?->water_meter_fee_housing_tenant !== null
                ? (float) $setting->water_meter_fee_housing_tenant
                : null,
        ], trans('api.success'));
    }
}
