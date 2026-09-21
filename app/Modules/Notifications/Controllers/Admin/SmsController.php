<?php

namespace App\Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendAdminSmsRequest;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Models\User;
use App\Services\TaqnyatSmsService;
use App\Support\SaudiMobile;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    use Responser;

    /**
     * Simple SMS API — accepts mobile + message only (sending not wired yet).
     *
     * POST /api/admin/sms/message
     * Body: { "mobile": "0512345678", "message": "..." }
     */
    public function sendMessage(SendAdminSmsRequest $request)
    {
        try {
            if (! $request->user() instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            $validated = $request->validated();

            return $this->apiResponse([
                'mobile' => $validated['mobile'],
                'message' => $validated['message'],
                'sent' => false,
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Send a free-text SMS from the admin panel (Taqnyat) — employee Bearer required.
     * Recipient is an employee, a client (user), or a raw mobile number.
     *
     * POST /api/admin/sms/send
     * To an employee: { "employee_id": 5, "message": "..." }
     * To a client:    { "user_id": 12, "message": "..." }
     * Raw number:     { "mobile": "0512345678", "message": "..." }
     * Multiple raw:   { "mobiles": ["0512345678", "0598765432"], "message": "..." }
     */
    public function send(Request $request)
    {
        try {
            if (! $request->user() instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            $validated = $request->validate([
                'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'mobile' => ['nullable', 'string', 'max:20'],
                'mobiles' => ['nullable', 'array', 'min:1', 'max:50'],
                'mobiles.*' => ['string', 'max:20'],
                'message' => ['required', 'string', 'max:1000'],
            ], [], [
                'employee_id' => 'الموظف',
                'user_id' => 'العميل',
                'mobile' => 'رقم الجوال',
                'mobiles' => 'أرقام الجوال',
                'message' => 'نص الرسالة',
            ]);

            [$targets, $error] = $this->resolveTargets($validated);
            if ($error !== null) {
                return $this->errorResponse($error, 422);
            }

            $message = trim($validated['message']);
            /** @var Employee $sender */
            $sender = $request->user();
            $sms = app(TaqnyatSmsService::class);

            $results = [];
            $sentCount = 0;

            foreach ($targets as $target) {
                $response = $sms->sendAndLog(
                    $message,
                    $target['mobile'],
                    'admin_manual:'.$sender->id.':'.$target['recipient_type'],
                    $target['user_id']
                );

                $sent = $response === true;
                $sendError = is_string($response) ? preg_replace('/^SMS Error:\s*/', '', $response) : ($sent ? null : 'send failed');

                $sentCount += $sent ? 1 : 0;
                $results[] = [
                    'recipient_type' => $target['recipient_type'],
                    'recipient_id' => $target['recipient_id'],
                    'recipient_name' => $target['recipient_name'],
                    'mobile' => $target['mobile'],
                    'sent' => $sent,
                    'error' => $sendError,
                ];
            }

            $payload = [
                'sent_count' => $sentCount,
                'total' => count($results),
                'message' => $message,
                'results' => $results,
            ];

            if ($sentCount === 0) {
                return $this->jsonResponse([
                    'message' => 'تعذر إرسال الرسالة',
                    'code' => 502,
                    'success' => false,
                    'data' => $payload,
                ], 502);
            }

            return $this->apiResponse(
                $payload,
                $sentCount === count($results)
                    ? 'تم إرسال الرسالة بنجاح'
                    : 'تم الإرسال جزئياً — بعض الأرقام فشلت'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Resolve recipients: employee_id → employees.phone, user_id → users.mobile,
     * otherwise raw mobile/mobiles.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: list<array{recipient_type: string, recipient_id: int|null, recipient_name: string|null, user_id: int|null, mobile: string}>, 1: array<string, list<string>>|null}
     */
    private function resolveTargets(array $validated): array
    {
        if (! empty($validated['employee_id'])) {
            $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
            $mobile = $this->normalizeOrNull($employee->phone);

            if ($mobile === null) {
                return [[], ['employee_id' => ['لا يوجد رقم جوال صالح مسجل لهذا الموظف.']]];
            }

            return [[[
                'recipient_type' => 'employee',
                'recipient_id' => $employee->id,
                'recipient_name' => $employee->name,
                'user_id' => null,
                'mobile' => $mobile,
            ]], null];
        }

        if (! empty($validated['user_id'])) {
            $user = User::query()->findOrFail((int) $validated['user_id']);
            $mobile = $this->normalizeOrNull($user->mobile);

            if ($mobile === null) {
                return [[], ['user_id' => ['لا يوجد رقم جوال صالح مسجل لهذا العميل.']]];
            }

            return [[[
                'recipient_type' => 'user',
                'recipient_id' => $user->id,
                'recipient_name' => $user->name,
                'user_id' => $user->id,
                'mobile' => $mobile,
            ]], null];
        }

        $rawMobiles = $validated['mobiles'] ?? (isset($validated['mobile']) && $validated['mobile'] !== null && $validated['mobile'] !== '' ? [$validated['mobile']] : []);

        if ($rawMobiles === []) {
            return [[], ['recipient' => ['حدد المستلم: employee_id أو user_id أو mobile.']]];
        }

        $targets = [];
        $invalid = [];
        $seen = [];

        foreach ($rawMobiles as $raw) {
            $mobile = $this->normalizeOrNull((string) $raw);
            if ($mobile === null) {
                $invalid[] = $raw;
                continue;
            }
            if (isset($seen[$mobile])) {
                continue;
            }
            $seen[$mobile] = true;

            $targets[] = [
                'recipient_type' => 'mobile',
                'recipient_id' => null,
                'recipient_name' => null,
                'user_id' => null,
                'mobile' => $mobile,
            ];
        }

        if ($invalid !== []) {
            return [[], ['mobile' => ['أرقام جوال غير صالحة: '.implode(', ', $invalid)]]];
        }

        return [$targets, null];
    }

    /**
     * Normalize any Saudi mobile format to 00966XXXXXXXXX, or null when invalid.
     */
    private function normalizeOrNull(?string $mobile): ?string
    {
        $national = SaudiMobile::toNational($mobile);

        if ($national === null || ! preg_match('/^5[0-9]{8}$/', $national)) {
            return null;
        }

        return '00966'.$national;
    }
}
