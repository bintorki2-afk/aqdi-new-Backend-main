<?php

namespace App\Modules\Content\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Website\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    public function store(SubscribeNewsletterRequest $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));

        NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $email],
            [
                'subscribed_at' => now(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]
        );

        return response()->json([
            'message' => trans('api.newsletter_subscribed'),
        ])->header('Cache-Control', 'no-store');
    }
}
