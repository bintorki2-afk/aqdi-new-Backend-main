<?php

namespace App\Services;

use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ContentPageService
{
    public const SUPPORTED_PAGES = ['home', 'about', 'faq', 'blogs', 'services'];

    public const PAGE_ALIASES = [
        'faqs' => 'faq',
    ];

    public const META_KEYS = ['meta_title', 'meta_description'];

    public function show(string $pageKey): array
    {
        $pageKey = $this->normalizePageKey($pageKey);

        return $this->buildResponsePayload($pageKey, $this->findOrCreatePage($pageKey));
    }

    /**
     * Public SEO for every listing page: `{ page, meta_title, meta_description }`.
     *
     * @return array<string, array{page: string, meta_title: string, meta_description: string}>
     */
    public function indexMeta(): array
    {
        $pages = [];

        foreach (self::SUPPORTED_PAGES as $pageKey) {
            $payload = $this->show($pageKey);
            $pages[$pageKey] = [
                'page' => $pageKey,
                'meta_title' => (string) ($payload['meta_title'] ?? ''),
                'meta_description' => (string) ($payload['meta_description'] ?? ''),
            ];
        }

        return $pages;
    }

    public function upsert(Request $request, string $pageKey): array
    {
        $pageKey = $this->normalizePageKey($pageKey);

        $request->validate([
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'meta' => ['sometimes', 'array'],
            'meta.meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta.meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $page = $this->findOrCreatePage($pageKey);

        $existingContent = is_array($page->content_json) ? $page->content_json : [];
        $baseContent = $this->mergeAssocRecursive($this->defaultContentFor($pageKey), $existingContent);

        $incomingMeta = $this->extractMetaPayload($request);
        $incomingSections = $this->extractSectionsPayload($request);
        $uploadedSections = $this->normalizeUploadedFiles($request->allFiles(), $pageKey);
        $this->deleteReplacedFiles(
            Arr::get($baseContent, 'sections', []),
            $uploadedSections
        );

        $incomingSections = $this->mergeAssocRecursive(
            $incomingSections,
            $uploadedSections
        );

        $mergedSections = $this->mergeAssocRecursive(
            Arr::get($baseContent, 'sections', []),
            $incomingSections
        );

        $content = [
            'page' => $pageKey,
            'meta_title' => array_key_exists('meta_title', $incomingMeta)
                ? $incomingMeta['meta_title']
                : (string) Arr::get($baseContent, 'meta_title', ''),
            'meta_description' => array_key_exists('meta_description', $incomingMeta)
                ? $incomingMeta['meta_description']
                : (string) Arr::get($baseContent, 'meta_description', ''),
            'sections' => $mergedSections,
        ];

        $page->update([
            'content_json' => $content,
        ]);

        return $this->buildResponsePayload($pageKey, $page->fresh());
    }

    public function fetchMessageFor(string $pageKey): string
    {
        return match ($pageKey) {
            'home' => 'Home content fetched successfully',
            'about' => 'About content fetched successfully',
            'blogs' => 'Blogs index SEO fetched successfully',
            'services' => 'Services index SEO fetched successfully',
            'faq' => 'FAQ index SEO fetched successfully',
            default => trans('api.success'),
        };
    }

    public function saveMessageFor(string $pageKey): string
    {
        return match ($pageKey) {
            'home' => 'Home content saved successfully',
            'about' => 'About content saved successfully',
            'blogs' => 'Blogs index SEO saved successfully',
            'services' => 'Services index SEO saved successfully',
            'faq' => 'FAQ index SEO saved successfully',
            default => trans('api.updated_successfully'),
        };
    }

    public function normalizePageKey(string $pageKey): string
    {
        $pageKey = trim(strtolower($pageKey));
        $pageKey = self::PAGE_ALIASES[$pageKey] ?? $pageKey;

        if (!in_array($pageKey, self::SUPPORTED_PAGES, true)) {
            throw ValidationException::withMessages([
                'page_key' => ['The selected page key is invalid.'],
            ]);
        }

        return $pageKey;
    }

    public function permissionSectionFor(string $pageKey): string
    {
        return match ($pageKey) {
            'blogs' => 'blogs',
            'services' => 'analytics',
            'faq' => 'faqs',
            default => 'app_content',
        };
    }

    /**
     * @return array{page: string, meta_title: string, meta_description: string}
     */
    public function publicMeta(string $pageKey): array
    {
        $payload = $this->show($pageKey);

        return [
            'page' => (string) ($payload['page'] ?? $pageKey),
            'meta_title' => (string) ($payload['meta_title'] ?? ''),
            'meta_description' => (string) ($payload['meta_description'] ?? ''),
        ];
    }

    private function findOrCreatePage(string $pageKey): ContentPage
    {
        $page = ContentPage::query()->where('page_key', $pageKey)->first();
        if ($page) {
            return $page;
        }

        $legacyKeys = array_keys(array_filter(
            self::PAGE_ALIASES,
            static fn (string $canonical): bool => $canonical === $pageKey
        ));

        if ($legacyKeys !== []) {
            $legacy = ContentPage::query()->whereIn('page_key', $legacyKeys)->first();
            if ($legacy) {
                $legacy->update(['page_key' => $pageKey]);

                return $legacy;
            }
        }

        return ContentPage::query()->create([
            'page_key' => $pageKey,
            'content_json' => $this->defaultContentFor($pageKey),
        ]);
    }

    private function buildResponsePayload(string $pageKey, ContentPage $page): array
    {
        $payload = $this->formatPageResponse($pageKey, $page->content_json);
        $payload['sections'] = $this->transformFileUrls($payload['sections'] ?? []);

        if ($this->isMetaOnlyPage($pageKey) && $payload['sections'] === []) {
            $payload['sections'] = (object) [];
        }

        $payload['updated_at'] = optional($page->updated_at)->toAtomString() ?? '';

        return $payload;
    }

    private function isMetaOnlyPage(string $pageKey): bool
    {
        return in_array($pageKey, ['blogs', 'services', 'faq'], true);
    }

    private function formatPageResponse(string $pageKey, mixed $content): array
    {
        $content = is_array($content) ? $content : $this->defaultContentFor($pageKey);
        $content = $this->mergeAssocRecursive($this->defaultContentFor($pageKey), $content);
        $content = $this->normalizeNullValues($content);
        $metaTitle = (string) Arr::get($content, 'meta_title', '');
        $metaDescription = (string) Arr::get($content, 'meta_description', '');

        return [
            'page' => $pageKey,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta' => [
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
            ],
            'sections' => Arr::get($content, 'sections', []),
        ];
    }

    /**
     * @return array{meta_title?: string, meta_description?: string}
     */
    private function extractMetaPayload(Request $request): array
    {
        $meta = [];
        $nested = $request->input('meta');

        if (is_array($nested)) {
            foreach (self::META_KEYS as $key) {
                if (array_key_exists($key, $nested)) {
                    $meta[$key] = $this->normalizeMetaString($nested[$key]);
                }
            }
        }

        foreach (self::META_KEYS as $key) {
            if ($request->exists($key)) {
                $meta[$key] = $this->normalizeMetaString($request->input($key));
            }
        }

        return $meta;
    }

    private function normalizeMetaString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function extractSectionsPayload(Request $request): array
    {
        $payload = $request->except([
            'page',
            'meta',
            'meta_title',
            'meta_description',
            '_method',
            '_token',
        ]);

        foreach (array_keys($payload) as $key) {
            if (str_starts_with((string) $key, 'deleted_')) {
                unset($payload[$key]);
            }
        }

        return $this->sanitizeNode($payload);
    }

    private function sanitizeNode(mixed $node): mixed
    {
        if (!is_array($node)) {
            return $node;
        }

        $sanitized = [];
        foreach ($node as $key => $value) {
            if ($value instanceof UploadedFile) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeNode($value);
        }

        return $sanitized;
    }

    private function normalizeUploadedFiles(array $files, string $pageKey, array $path = []): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            $currentPath = [...$path, (string) $key];

            if ($value instanceof UploadedFile) {
                $targetKey = $this->uploadedFileTargetKey((string) $key);
                $normalized[$targetKey] = $this->storeUploadedFile($value, $pageKey, $currentPath);

                if ($targetKey === 'license_file_url') {
                    $normalized['license_file_type'] = $this->detectLicenseType($value);
                }

                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeUploadedFiles($value, $pageKey, $currentPath);
            }
        }

        return $normalized;
    }

    private function uploadedFileTargetKey(string $key): string
    {
        return match ($key) {
            'image' => 'image_url',
            'license_file' => 'license_file_url',
            default => $key.'_url',
        };
    }

    private function storeUploadedFile(UploadedFile $file, string $pageKey, array $path): string
    {
        $segments = array_map(function (string $segment): string {
            return preg_replace('/[^A-Za-z0-9\-_]/', '-', $segment) ?: 'field';
        }, array_slice($path, 0, -1));

        $folder = 'content-pages/'.$pageKey;
        if ($segments !== []) {
            $folder .= '/'.implode('/', $segments);
        }

        return fileUploader($file, $folder);
    }

    private function detectLicenseType(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return $extension === 'pdf' ? 'pdf' : 'image';
    }

    private function transformFileUrls(mixed $node): mixed
    {
        if (!is_array($node)) {
            return $node;
        }

        $urlKeys = ['image_url', 'license_file_url'];
        $result = [];

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->transformFileUrls($value);
                continue;
            }

            if (in_array((string) $key, $urlKeys, true)) {
                $result[$key] = $this->toPublicUrl(is_string($value) ? $value : '');
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function toPublicUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return url('storage/'.$path);
    }

    private function deleteReplacedFiles(mixed $existing, mixed $uploaded): void
    {
        if (!is_array($existing) || !is_array($uploaded)) {
            return;
        }

        $fileKeys = ['image_url', 'license_file_url'];

        foreach ($uploaded as $key => $value) {
            if (is_array($value)) {
                $this->deleteReplacedFiles($existing[$key] ?? [], $value);
                continue;
            }

            if (!in_array((string) $key, $fileKeys, true)) {
                continue;
            }

            $oldPath = $existing[$key] ?? null;
            if (!is_string($oldPath) || trim($oldPath) === '') {
                continue;
            }

            if (str_starts_with($oldPath, 'http://') || str_starts_with($oldPath, 'https://')) {
                continue;
            }

            deleteFile($oldPath);
        }
    }

    private function mergeAssocRecursive(mixed $existing, mixed $incoming): mixed
    {
        if (!is_array($incoming)) {
            return $incoming;
        }

        if ($incoming === []) {
            if (is_array($existing) && $this->isAssoc($existing)) {
                return $existing;
            }

            return [];
        }

        if (!$this->isAssoc($incoming)) {
            return $this->mergeList($existing, $incoming);
        }

        $result = is_array($existing) ? $existing : [];

        foreach ($incoming as $key => $value) {
            if (str_starts_with((string) $key, 'keep_')) {
                continue;
            }

            $result[$key] = array_key_exists($key, $result)
                ? $this->mergeAssocRecursive($result[$key], $value)
                : $value;
        }

        return $this->applyKeepFlags($result, is_array($existing) ? $existing : [], $incoming);
    }

    private function mergeList(mixed $existing, array $incoming): array
    {
        $existing = is_array($existing) ? array_values($existing) : [];

        if ($incoming === []) {
            return [];
        }

        $allIncomingItemsAreArrays = collect($incoming)->every(fn ($item) => is_array($item));
        $allIncomingItemsHaveIds = $allIncomingItemsAreArrays
            && collect($incoming)->every(fn (array $item) => array_key_exists('id', $item) && $item['id'] !== null && $item['id'] !== '');

        if (!$allIncomingItemsHaveIds) {
            return array_map(fn ($item) => $this->sanitizeNode($item), $incoming);
        }

        $existingById = collect($existing)
            ->filter(fn ($item) => is_array($item) && array_key_exists('id', $item))
            ->keyBy(fn (array $item) => (string) $item['id']);

        $result = [];
        foreach ($incoming as $item) {
            $existingItem = $existingById->get((string) $item['id'], []);
            $result[] = $this->mergeAssocRecursive($existingItem, $item);
        }

        return $result;
    }

    private function applyKeepFlags(array $result, array $existing, array $incoming): array
    {
        $fileFieldMap = [
            'keep_image' => 'image_url',
            'keep_license' => 'license_file_url',
            'keep_license_file' => 'license_file_url',
        ];

        foreach ($fileFieldMap as $keepKey => $targetKey) {
            if (!array_key_exists($keepKey, $incoming)) {
                continue;
            }

            $keepValue = filter_var($incoming[$keepKey], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $keepValue = $keepValue ?? in_array($incoming[$keepKey], [1, '1'], true);

            if ($keepValue && !array_key_exists($targetKey, $incoming) && array_key_exists($targetKey, $existing)) {
                $result[$targetKey] = $existing[$targetKey];
            }

            if (!$keepValue && !array_key_exists($targetKey, $incoming)) {
                $result[$targetKey] = null;
            }
        }

        return $result;
    }

    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function normalizeNullValues(mixed $value): mixed
    {
        if ($value === null) {
            return '';
        }

        if (!is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeNullValues($item);
        }

        return $normalized;
    }

    private function defaultContentFor(string $pageKey): array
    {
        return match ($pageKey) {
            'home' => [
                'page' => 'home',
                'meta_title' => '',
                'meta_description' => '',
                'sections' => [
                    'hero' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'image_url' => '',
                    ],
                    'official_authorities' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'features' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'pricing' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'contact' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'contact_number' => '',
                        'image_url' => '',
                    ],
                    'app' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'image_url' => '',
                    ],
                ],
            ],
            'about' => [
                'page' => 'about',
                'meta_title' => '',
                'meta_description' => '',
                'sections' => [
                    'hero' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                    ],
                    'story' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'vision_mission' => [
                        'section_title' => '',
                        'section_description' => '',
                        'mission' => [
                            'badge_text' => '',
                            'title' => '',
                            'description' => '',
                            'image_url' => '',
                        ],
                        'vision' => [
                            'badge_text' => '',
                            'title' => '',
                            'description' => '',
                            'image_url' => '',
                        ],
                    ],
                    'beneficiaries' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                    'values' => [
                        'badge_text' => '',
                        'main_title' => '',
                        'description' => '',
                        'cards' => [],
                    ],
                ],
            ],
            default => [
                'page' => $pageKey,
                'meta_title' => '',
                'meta_description' => '',
                'sections' => [],
            ],
        };
    }
}
