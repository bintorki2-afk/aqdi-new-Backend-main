<?php

namespace App\Modules\Auth\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SignupUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fname' => 'required|string|max:255',
            'mobile' => 'required|string|max:15|unique:users,mobile',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
            'fcm_token' => 'sometimes',
            'platform' => 'nullable|string|in:website,web,google_play,google,android,apple_store,ios,apple,appstore,app_store',
            'utm_source' => 'nullable|string|max:64',
            'utm_medium' => 'nullable|string|max:64',
            'utm_campaign' => 'nullable|string|max:191',
            'utm_term' => 'nullable|string|max:191',
            'utm_content' => 'nullable|string|max:191',
            'gclid' => 'nullable|string|max:191',
            'fbclid' => 'nullable|string|max:191',
            'ttclid' => 'nullable|string|max:191',
            'twclid' => 'nullable|string|max:191',
            'sccid' => 'nullable|string|max:191',
        ];
    }
}
