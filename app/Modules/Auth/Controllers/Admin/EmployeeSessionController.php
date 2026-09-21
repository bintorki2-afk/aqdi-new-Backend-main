<?php

namespace App\Modules\Auth\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\EmployeeAuthResource;
use App\Models\Employee;
use App\Modules\Auth\Requests\Admin\EmployeeLoginRequest;
use App\Modules\Auth\Services\EmployeeTokenService;
use App\Shared\Responses\Responser;
use App\Support\AuthenticatedEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EmployeeSessionController extends Controller
{
    use Responser;

    public function login_check(EmployeeLoginRequest $request, EmployeeTokenService $tokenService)
    {
        try {
            $validated = $request->validated();

            $employee = Employee::with('roleRelation')
                ->where('email', $validated['email'])
                ->first();

            if (! $employee || ! Hash::check($validated['password'], $employee->password)) {
                return response()->json([
                    'message' => trans('api.credentials_error'),
                    'success' => false,
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (! $employee->is_active) {
                return response()->json([
                    'message' => trans('api.employee_inactive'),
                    'code' => 'forbidden',
                    'success' => false,
                ], Response::HTTP_FORBIDDEN);
            }

            if ($employee->blocked_until && now()->lessThan($employee->blocked_until)) {
                return response()->json([
                    'message' => trans('api.employee_account_blocked'),
                    'code' => 'forbidden',
                    'success' => false,
                ], Response::HTTP_FORBIDDEN);
            }

            if (array_key_exists('fcm_token', $validated) && filled($validated['fcm_token'])) {
                $employee->fcm_token = $validated['fcm_token'];
                $employee->save();
            }

            $employee->tokens()->delete();
            $employee->refreshTokens()->delete();

            $remembered = (bool) ($validated['remember_me']
                ?? $validated['rememberMe']
                ?? $validated['remember']
                ?? false);
            $tokens = $tokenService->issueTokenPair($employee, $remembered);

            return $this->apiResponse(
                new EmployeeAuthResource($employee, $tokens),
                trans('api.login_success')
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => trans('api.error_occurred'),
                'success' => false,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function refreshToken(Request $request, EmployeeTokenService $tokenService)
    {
        try {
            $refreshToken = $request->input('refresh_token');

            if (! is_string($refreshToken) || $refreshToken === '') {
                return $this->errorMessage(trans('api.unauthorized'), 401);
            }

            $rotation = $tokenService->rotate($refreshToken);

            if (! $rotation) {
                return $this->errorMessage(trans('api.unauthorized'), 401);
            }

            return $this->apiResponse(
                new EmployeeAuthResource($rotation['employee'], $rotation['tokens']),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function profile(Request $request)
    {
        try {
            $employee = AuthenticatedEmployee::from($request);

            if (! $employee instanceof Employee) {
                return $this->errorMessage(trans('api.unauthenticated'), 401);
            }

            $employee->loadMissing('roleRelation.permissions');

            return $this->apiResponse(
                new EmployeeAuthResource($employee),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function logout(Request $request, EmployeeTokenService $tokenService)
    {
        try {
            $employee = $request->user();

            if ($employee instanceof Employee) {
                $refreshToken = $request->input('refresh_token');
                $tokenService->revoke(
                    $employee,
                    is_string($refreshToken) ? $refreshToken : null
                );
            }

            $request->user()?->currentAccessToken()?->delete();

            return $this->successMessage(trans('api.logout_success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function updateFcmToken(Request $request)
    {
        try {
            $employee = AuthenticatedEmployee::from($request);
            if (! $employee instanceof Employee) {
                return $this->errorMessage(trans('api.unauthenticated'), 401);
            }

            $validated = $request->validate([
                'fcm_token' => ['required', 'string'],
            ]);

            $employee->update(['fcm_token' => $validated['fcm_token']]);

            return $this->apiResponse(
                ['fcm_token' => $employee->fcm_token],
                trans('api.updated_successfully')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
