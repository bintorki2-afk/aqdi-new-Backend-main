<?php

namespace App\Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmsSettingController extends Controller
{
    use Responser;

    /**
     * Project-wide SMS templates (single row in settings).
     * GET /api/admin/sms-settings
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
     * Create/update project-wide SMS templates.
     * POST /api/admin/sms-settings
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'sms_user' => ['nullable', 'string', 'max:5000'],
                'sms_owner' => ['nullable', 'string', 'max:5000'],
                'sms_employee' => ['nullable', 'string', 'max:5000'],
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
     * @return array<string, mixed>
     */
    private function payload(?Setting $setting = null): array
    {
        $setting ??= Setting::query()->first() ?? Setting::query()->create([]);

        return [
            'sms_user' => $setting->sms_user,
            'sms_owner' => $setting->sms_owner,
            'sms_employee' => $setting->sms_employee,
        ];
    }
}
