<?php

namespace App\Modules\Seo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Services\Seo\GoogleSeoOAuthService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

class GoogleSeoController extends Controller
{
    use Responser;

    public function __construct(protected GoogleSeoOAuthService $oauth) {}

    public function status()
    {
        try {
            return $this->apiResponse($this->oauth->status(), trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function connect(Request $request)
    {
        try {
            $employee = $request->user();
            if (! $employee instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            return $this->apiResponse([
                'auth_url' => $this->oauth->authorizationUrl($employee),
            ], trans('api.google_seo_redirect'));
        } catch (RuntimeException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            if ($request->filled('error')) {
                return redirect()->away($this->oauth->frontendRedirect(false, (string) $request->query('error')));
            }

            $state = (string) $request->query('state', '');
            if ($state === '') {
                return redirect()->away($this->oauth->frontendRedirect(false, 'missing_state'));
            }

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($this->oauth->redirectUri())
                ->user();

            $this->oauth->complete($state, $googleUser);

            return redirect()->away($this->oauth->frontendRedirect(true));
        } catch (Throwable $e) {
            return redirect()->away($this->oauth->frontendRedirect(false, $e->getMessage()));
        }
    }

    public function disconnect()
    {
        try {
            $this->oauth->disconnect();

            return $this->apiResponse($this->oauth->status(), trans('api.google_seo_disconnected'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
