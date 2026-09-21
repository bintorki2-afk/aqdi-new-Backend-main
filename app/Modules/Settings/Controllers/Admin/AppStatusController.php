<?php

namespace App\Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\AppStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppStatusController extends Controller
{
    use Responser;

    public function __construct(protected AppStatusService $appStatus)
    {
    }

    /**
     * GET /api/admin/settings/app-status
     * Website/mobile open flags + iOS/Android version gates.
     */
    public function show()
    {
        try {
            return $this->apiResponse($this->appStatus->adminPayload(), trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/settings/app-status
     *
     * Close website:  { "website": { "is_open": false } }
     * Close mobile:   { "mobile": { "is_open": false } }
     * iOS version:    { "ios": { "latest_version": "1.2.0", "min_version": "1.1.0", "force_update": false, "store_url": "https://..." } }
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'website' => ['sometimes'],
                'website.is_open' => ['sometimes', 'boolean'],
                'mobile' => ['sometimes'],
                'mobile.is_open' => ['sometimes', 'boolean'],
                'ios' => ['sometimes', 'array'],
                'android' => ['sometimes', 'array'],
                ...$this->platformRules('ios'),
                ...$this->platformRules('android'),
            ]);

            return $this->apiResponse(
                $this->appStatus->update($validated),
                trans('api.updated_successfully')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function platformRules(string $platform): array
    {
        return [
            "{$platform}.latest_version" => ['nullable', 'string', 'max:50'],
            "{$platform}.min_version" => ['nullable', 'string', 'max:50'],
            "{$platform}.force_update" => ['sometimes', 'boolean'],
            "{$platform}.store_url" => ['nullable', 'string', 'max:500'],
            "{$platform}.message_ar" => ['nullable', 'string', 'max:2000'],
            "{$platform}.message_en" => ['nullable', 'string', 'max:2000'],
        ];
    }
}
