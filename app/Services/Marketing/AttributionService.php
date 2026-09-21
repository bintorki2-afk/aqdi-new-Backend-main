<?php

namespace App\Services\Marketing;

use App\Models\Contract;
use App\Models\User;
use App\Support\Marketing\AttributionSchema;
use App\Support\Marketing\UtmAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AttributionService
{
    public function resolve(Request $request): UtmAttribution
    {
        $fromRequest = UtmAttribution::fromRequest($request);
        if (! $fromRequest->isEmpty()) {
            return $fromRequest;
        }

        $fromCookie = UtmAttribution::fromCookie($request);
        if (! $fromCookie->isEmpty()) {
            return $fromCookie;
        }

        return UtmAttribution::fromSession($request);
    }

    public function persistFirstTouch(Request $request): ?UtmAttribution
    {
        $incoming = UtmAttribution::fromRequest($request);
        if ($incoming->isEmpty()) {
            return null;
        }

        $existing = UtmAttribution::fromCookie($request);
        if (! $existing->isEmpty()) {
            return $existing;
        }

        $minutes = (int) config('ads.attribution.cookie_minutes', 60 * 24 * 90);
        cookie()->queue(
            (string) config('ads.attribution.cookie'),
            $incoming->toJson(),
            $minutes,
            '/',
            null,
            (bool) config('session.secure', false),
            true,
            false,
            'lax'
        );

        if ($request->hasSession() && ! $request->session()->has((string) config('ads.attribution.session_key'))) {
            $request->session()->put((string) config('ads.attribution.session_key'), $incoming->toArray());
        }

        return $incoming;
    }

    public function stampUser(User $user, Request $request): void
    {
        if (! $this->hasAttributionColumns($user)) {
            return;
        }

        $attribution = $this->resolve($request);
        if ($attribution->isEmpty()) {
            return;
        }

        $this->fillFirstTouch($user, $attribution);
    }

    public function stampContract(Contract $contract, Request $request): void
    {
        if (! $this->hasAttributionColumns($contract)) {
            return;
        }

        $fromClick = UtmAttribution::fromRequest($request);
        $attribution = $fromClick->isEmpty() ? $this->resolve($request) : $fromClick;

        if ($attribution->isEmpty() && $contract->user_id) {
            $user = $contract->relationLoaded('user')
                ? $contract->user
                : User::query()->find($contract->user_id);
            if ($user instanceof User && $this->hasAttributionColumns($user)) {
                $attribution = UtmAttribution::fromArray($user->only(UtmAttribution::FIELD_NAMES));
            }
        }

        if ($attribution->isEmpty()) {
            return;
        }

        $this->fillFirstTouch($contract, $attribution);
    }

    public function stampOnCreating(Model $model): void
    {
        $request = request();
        if (! $request instanceof Request) {
            return;
        }

        if ($model instanceof User) {
            $this->stampUser($model, $request);

            return;
        }

        if ($model instanceof Contract) {
            $this->stampContract($model, $request);
        }
    }

    private function fillFirstTouch(Model $model, UtmAttribution $attribution): void
    {
        $filled = false;
        foreach ($attribution->toArray() as $field => $value) {
            if (! AttributionSchema::hasColumn($model->getTable(), $field)) {
                continue;
            }

            $current = $model->getAttribute($field);
            if ($current === null || $current === '') {
                $model->setAttribute($field, $value);
                $filled = true;
            }
        }

        if ($filled && AttributionSchema::hasColumn($model->getTable(), 'attributed_at')
            && ($model->getAttribute('attributed_at') === null || $model->getAttribute('attributed_at') === '')) {
            $model->setAttribute('attributed_at', now());
        }
    }

    private function hasAttributionColumns(Model $model): bool
    {
        return AttributionSchema::hasColumn($model->getTable(), 'utm_source');
    }
}
