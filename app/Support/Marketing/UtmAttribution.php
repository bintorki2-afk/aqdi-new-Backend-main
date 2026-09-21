<?php

namespace App\Support\Marketing;

use Illuminate\Http\Request;

final class UtmAttribution
{
    public const FIELD_NAMES = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'ttclid',
        'twclid',
        'sccid',
    ];

    public function __construct(
        public ?string $utm_source = null,
        public ?string $utm_medium = null,
        public ?string $utm_campaign = null,
        public ?string $utm_term = null,
        public ?string $utm_content = null,
        public ?string $gclid = null,
        public ?string $fbclid = null,
        public ?string $ttclid = null,
        public ?string $twclid = null,
        public ?string $sccid = null,
    ) {
        $this->utm_source = self::normalizeSource($this->utm_source);
        $this->utm_medium = self::normalizeText($this->utm_medium);
        $this->utm_campaign = self::normalizeText($this->utm_campaign);
        $this->utm_term = self::normalizeText($this->utm_term);
        $this->utm_content = self::normalizeText($this->utm_content);
        $this->gclid = self::normalizeText($this->gclid);
        $this->fbclid = self::normalizeText($this->fbclid);
        $this->ttclid = self::normalizeText($this->ttclid);
        $this->twclid = self::normalizeText($this->twclid);
        $this->sccid = self::normalizeText($this->sccid);

        if ($this->utm_source === null) {
            $this->utm_source = self::sourceFromClickIds($this->toArray());
        }
    }

    public static function fromRequest(Request $request): self
    {
        $clickId = $request->input('sccid', $request->input('ScCid'));

        return new self(
            utm_source: $request->input('utm_source'),
            utm_medium: $request->input('utm_medium'),
            utm_campaign: $request->input('utm_campaign'),
            utm_term: $request->input('utm_term'),
            utm_content: $request->input('utm_content'),
            gclid: $request->input('gclid'),
            fbclid: $request->input('fbclid'),
            ttclid: $request->input('ttclid'),
            twclid: $request->input('twclid'),
            sccid: is_string($clickId) ? $clickId : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            utm_source: isset($payload['utm_source']) ? (string) $payload['utm_source'] : null,
            utm_medium: isset($payload['utm_medium']) ? (string) $payload['utm_medium'] : null,
            utm_campaign: isset($payload['utm_campaign']) ? (string) $payload['utm_campaign'] : null,
            utm_term: isset($payload['utm_term']) ? (string) $payload['utm_term'] : null,
            utm_content: isset($payload['utm_content']) ? (string) $payload['utm_content'] : null,
            gclid: isset($payload['gclid']) ? (string) $payload['gclid'] : null,
            fbclid: isset($payload['fbclid']) ? (string) $payload['fbclid'] : null,
            ttclid: isset($payload['ttclid']) ? (string) $payload['ttclid'] : null,
            twclid: isset($payload['twclid']) ? (string) $payload['twclid'] : null,
            sccid: isset($payload['sccid']) ? (string) $payload['sccid'] : (isset($payload['ScCid']) ? (string) $payload['ScCid'] : null),
        );
    }

    public static function fromCookie(Request $request): self
    {
        $raw = $request->cookie((string) config('ads.attribution.cookie'));
        if (! is_string($raw) || trim($raw) === '') {
            return new self;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return new self;
        }

        return self::fromArray($decoded);
    }

    public static function fromSession(Request $request): self
    {
        if (! $request->hasSession()) {
            return new self;
        }

        $payload = $request->session()->get((string) config('ads.attribution.session_key'));
        if (! is_array($payload)) {
            return new self;
        }

        return self::fromArray($payload);
    }

    public function isEmpty(): bool
    {
        foreach ($this->toArray() as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $payload = [];
        foreach (self::FIELD_NAMES as $field) {
            $value = $this->{$field};
            if (is_string($value) && $value !== '') {
                $payload[$field] = $value;
            }
        }

        return $payload;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public static function normalizeSource(?string $source): ?string
    {
        $normalized = self::normalizeText($source);
        if ($normalized === null) {
            return null;
        }

        $key = strtolower($normalized);
        $aliases = config('ads.utm.aliases', []);

        return $aliases[$key] ?? $key;
    }

    public static function sourceLabel(string $source, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $sources = config('ads.utm.sources', []);
        $row = $sources[$source] ?? null;
        if (! is_array($row)) {
            return $source;
        }

        return (string) ($row[$locale] ?? $row['ar'] ?? $source);
    }

    public static function buildQuery(string $source, string $campaign, ?string $term = null, ?string $content = null, ?string $medium = null): string
    {
        $canonical = self::normalizeSource($source) ?? 'direct';
        $query = [
            'utm_source' => $canonical,
            'utm_medium' => $medium ?: (string) config('ads.utm.medium_default', 'cpc'),
            'utm_campaign' => $campaign,
        ];
        if ($term) {
            $query['utm_term'] = $term;
        }
        if ($content) {
            $query['utm_content'] = $content;
        }

        return http_build_query($query);
    }

    /**
     * @param  array<string, string>  $payload
     */
    private static function sourceFromClickIds(array $payload): ?string
    {
        foreach (config('ads.utm.click_ids', []) as $param => $source) {
            if (! empty($payload[$param])) {
                return $source;
            }
        }

        return null;
    }

    private static function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
