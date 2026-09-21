<?php

namespace App\Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class NotificationController extends Controller
{
    use Responser;

    public function __construct(
        protected FirebaseNotificationService $firebase
    ) {}

    /**
     * POST /api/admin/notifications/send
     *
     * audience: user | custom_user | employee | custom_employee | all_users | all_employees
     */
    public function send(Request $request)
    {
        return $this->dispatchNotification($request, null);
    }

    /** POST /api/admin/notifications/user */
    public function sendToUser(Request $request)
    {
        return $this->dispatchNotification($request, 'user');
    }

    /** POST /api/admin/notifications/custom-user */
    public function sendToCustomUser(Request $request)
    {
        return $this->dispatchNotification($request, 'custom_user');
    }

    /** POST /api/admin/notifications/employee */
    public function sendToEmployee(Request $request)
    {
        return $this->dispatchNotification($request, 'employee');
    }

    /** POST /api/admin/notifications/custom-employee */
    public function sendToCustomEmployee(Request $request)
    {
        return $this->dispatchNotification($request, 'custom_employee');
    }

    /** POST /api/admin/notifications/all-users */
    public function sendToAllUsers(Request $request)
    {
        return $this->dispatchNotification($request, 'all_users');
    }

    /** POST /api/admin/notifications/all-employees */
    public function sendToAllEmployees(Request $request)
    {
        return $this->dispatchNotification($request, 'all_employees');
    }

    private function dispatchNotification(Request $request, ?string $forcedAudience)
    {
        try {
            if ($forcedAudience !== null) {
                $request->merge(['audience' => $forcedAudience]);
            }

            $validated = $request->validate([
                'audience' => ['required', Rule::in([
                    'user',
                    'custom_user',
                    'employee',
                    'custom_employee',
                    'all_users',
                    'all_employees',
                ])],
                'user_id' => [
                    Rule::requiredIf(fn () => in_array($request->input('audience'), ['user', 'custom_user'], true)),
                    'nullable',
                    'integer',
                    'exists:users,id',
                ],
                'employee_id' => [
                    Rule::requiredIf(fn () => in_array($request->input('audience'), ['employee', 'custom_employee'], true)),
                    'nullable',
                    'integer',
                    'exists:employees,id',
                ],
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string', 'max:5000'],
                'data' => ['nullable', 'array'],
            ]);

            $audience = (string) $validated['audience'];
            $title = (string) $validated['title'];
            $body = (string) $validated['body'];
            $data = is_array($validated['data'] ?? null) ? $validated['data'] : [];

            $result = match ($audience) {
                'user', 'custom_user' => $this->firebase->sendToUser(
                    (int) $validated['user_id'],
                    $title,
                    $body,
                    $data
                ),
                'employee', 'custom_employee' => $this->firebase->sendToEmployee(
                    (int) $validated['employee_id'],
                    $title,
                    $body,
                    $data
                ),
                'all_users' => $this->firebase->sendToAllUsers($title, $body, $data),
                'all_employees' => $this->firebase->sendToAllEmployees($title, $body, $data),
            };

            if (! empty($result['missing_token'])) {
                return $this->errorMessage('لا يوجد FCM token للمستلم.', 422);
            }

            return $this->apiResponse([
                'audience' => $audience,
                'title' => $title,
                'body' => $body,
                'result' => $result,
            ], trans('api.success'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
