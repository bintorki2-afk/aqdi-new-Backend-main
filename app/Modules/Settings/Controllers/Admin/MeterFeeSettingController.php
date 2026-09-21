<?php

namespace App\Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeterFeeSettingController extends Controller
{
    use Responser;

    /**
     * Project-wide meter fees (housing/commercial only — one row).
     * GET /api/admin/meter-fee-settings
     */
    public function show()
    {
        try {
            return $this->apiResponse($this->payload(), trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/meter-fee-settings
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'electricity_meter_fee_commercial_tenant' => ['nullable', 'numeric', 'min:0'],
                'electricity_meter_fee_housing_tenant' => ['nullable', 'numeric', 'min:0'],
                'water_meter_fee_commercial_tenant' => ['nullable', 'numeric', 'min:0'],
                'water_meter_fee_housing_tenant' => ['nullable', 'numeric', 'min:0'],
            ]);

            $setting = Setting::query()->first() ?? Setting::query()->create([]);
            $setting->update($validated);

            return $this->apiResponse($this->payload($setting->fresh()), trans('api.updated_successfully'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, float|null>
     */
    private function payload(?Setting $setting = null): array
    {
        $setting ??= Setting::query()->first() ?? Setting::query()->create([]);

        return [
            'electricity_meter_fee_commercial_tenant' => $setting->electricity_meter_fee_commercial_tenant !== null
                ? (float) $setting->electricity_meter_fee_commercial_tenant
                : null,
            'electricity_meter_fee_housing_tenant' => $setting->electricity_meter_fee_housing_tenant !== null
                ? (float) $setting->electricity_meter_fee_housing_tenant
                : null,
            'water_meter_fee_commercial_tenant' => $setting->water_meter_fee_commercial_tenant !== null
                ? (float) $setting->water_meter_fee_commercial_tenant
                : null,
            'water_meter_fee_housing_tenant' => $setting->water_meter_fee_housing_tenant !== null
                ? (float) $setting->water_meter_fee_housing_tenant
                : null,
        ];
    }
}
