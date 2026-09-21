<?php

namespace App\Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GeneralSettingController extends Controller
{
    use Responser;

    public function index()
    {
        try {
            GeneralSetting::syncFromConfig();
            $settings = GeneralSetting::query()->get()->keyBy('key');

            $data = [];
            foreach (array_keys(config('general_settings', [])) as $key) {
                $setting = $settings->get($key);
                if ($setting === null) {
                    continue;
                }
                $data[$key] = [
                    'key' => $setting->key,
                    'label' => $setting->label_ar,
                    'enabled' => $setting->enabled,
                ];
            }

            return $this->apiResponse($data, 'تم جلب الإعدادات بنجاح');
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $key)
    {
        try {
            $setting = GeneralSetting::query()->where('key', $key)->first();

            if ($setting === null) {
                return $this->errorMessage('الإعداد غير موجود', 404);
            }

            $validator = Validator::make($request->all(), [
                'enabled' => ['required', 'boolean'],
            ], [
                'enabled.required' => 'The enabled field must be boolean.',
                'enabled.boolean' => 'The enabled field must be boolean.',
            ]);

            if ($validator->fails()) {
                return $this->booleanValidationError($validator->errors());
            }

            $setting->update(['enabled' => $request->boolean('enabled')]);

            return $this->apiResponse([
                'key' => $setting->key,
                'label' => $setting->label_ar,
                'enabled' => $setting->enabled,
                'updated_at' => $this->isoTimestamp($setting->updated_at),
            ], 'تم تحديث الإعداد بنجاح');
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {
            GeneralSetting::syncFromConfig();
            $validKeys = array_keys(config('general_settings', []));
            $payload = $request->all();

            $unknownKeys = array_diff(array_keys($payload), $validKeys);
            if ($unknownKeys !== []) {
                return $this->errorMessage('الإعداد غير موجود', 404);
            }

            $rules = [];
            foreach ($validKeys as $key) {
                $rules[$key] = ['sometimes', 'boolean'];
            }

            $validator = Validator::make($payload, $rules, array_merge(
                ...array_map(fn (string $key) => [
                    "{$key}.boolean" => 'The '.$key.' field must be boolean.',
                ], $validKeys)
            ));

            if ($validator->fails()) {
                return $this->booleanValidationError($validator->errors());
            }

            $settings = GeneralSetting::query()->whereIn('key', $validKeys)->get()->keyBy('key');
            $now = now();

            foreach ($validKeys as $key) {
                if (! array_key_exists($key, $payload)) {
                    continue;
                }
                $settings->get($key)?->update(['enabled' => (bool) $payload[$key]]);
            }

            $data = [];
            foreach ($validKeys as $key) {
                $data[$key] = (bool) ($settings->get($key)?->enabled ?? config("general_settings.{$key}.default"));
            }
            $data['updated_at'] = $this->isoTimestamp($now);

            return $this->apiResponse($data, 'تم تحديث الإعدادات بنجاح');
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function booleanValidationError(\Illuminate\Support\MessageBag $errors)
    {
        return $this->jsonResponse([
            'message' => 'قيمة enabled يجب أن تكون true أو false',
            'errors' => $errors,
            'code' => 422,
            'success' => false,
        ], 422);
    }

    private function isoTimestamp(string|Carbon $value): string
    {
        return Carbon::parse($value)->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
