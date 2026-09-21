<?php

namespace App\Modules\Settings\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Setting;

class SmsSettingController extends Controller
{
    use Responser;

    /**
     * GET /api/v2/sms-settings
     */
    public function show()
    {
        $setting = Setting::query()->first();

        return $this->apiResponse([
            'sms_user' => $setting?->sms_user,
            'sms_owner' => $setting?->sms_owner,
            'sms_employee' => $setting?->sms_employee,
        ], trans('api.success'));
    }
}
