<?php

namespace App\Services\Seo;

use DOMDocument;
use DOMElement;
use DOMXPath;

class HtmlPageParser
{
    /**
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     h1s: list<string>,
     *     image_count: int,
     *     images_missing_alt: int,
     *     hrefs: list<string>,
     *     noindex: bool,
     *     canonical: ?string
     * }
     */
    public function parse(string $html, string $pageUrl): array
    {
        $empty = [
            'title' => null,
            'description' => null,
            'h1s' => [],
            'image_count' => 0,
            'images_missing_alt' => 0,
            'hrefs' => [],
            'noindex' => false,
            'canonical' => null,
        ];

        if (trim($html) === '') {
            return $empty;
        }

        $dom = new DOMDocument();
        $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        if (! $loaded) {
            return $empty;
        }

        $xpath = new DOMXPath($dom);

        $title = $this->firstText($xpath, '//title');
        $description = $this->metaContent($xpath, 'description');
        $robots = strtolower((string) $this->metaContent($xpath, 'robots'));
        $googlebot = strtolower((string) $this->metaContent($xpath, 'googlebot'));
        $noindex = str_contains($robots, 'noindex') || str_contains($googlebot, 'noindex');

        $h1s = [];
        foreach ($xpath->query('//h1') ?: [] as $node) {
            $text = $this->normalizeText($node->textContent ?? '');
            if ($text !== '') {
                $h1s[] = $text;
            }
        }

        $imageCount = 0;
        $missingAlt = 0;
        foreach ($xpath->query('//img') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $imageCount++;
            $alt = trim($node->getAttribute('alt'));
            if ($alt === '') {
                $missingAlt++;
            }
        }

        $hrefs = [];
        foreach ($xpath->query('//a[@href]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $resolved = $this->resolveUrl($pageUrl, $node->getAttribute('href'));
            if ($resolved !== null) {
                $hrefs[] = $resolved;
            }
        }

        $canonical = null;
        foreach ($xpath->query('//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $canonical = $this->resolveUrl($pageUrl, $node->getAttribute('href'));
                break;
            }
        }

        return [
            'title' => $title,
            'description' => $description,
            'h1s' => $h1s,
            'image_count' => $imageCount,
            'images_missing_alt' => $missingAlt,
            'hrefs' => array_values(array_unique($hrefs)),
            'noindex' => $noindex,
            'canonical' => $canonical,
        ];
    }

    public function resolveUrl(string $base, string $href): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }

        $lower = strtolower($href);
        foreach (['mailto:', 'tel:', 'javascript:', 'data:', 'sms:'] as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return null;
            }
        }

        $hashPos = strpos($href, '#');
        if ($hashPos !== false) {
            $href = substr($href, 0, $hashPos);
        }
        if ($href === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';

            return $scheme.':'.$href;
        }

        $parts = parse_url($base);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $origin = $scheme.'://'.$host.$port;

        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        $basePath = $parts['path'] ?? '/';
        if (! str_ends_with($basePath, '/')) {
            $basePath = preg_replace('#/[^/]*$#', '/', $basePath) ?: '/';
        }

        return $origin.$this->collapseDots($basePath.$href);
    }

    private function collapseDots(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                if ($segment === '' && $segments === []) {
                    $segments[] = '';
                }
                continue;
            }
            if ($segment === '..') {
                if (count($segments) > 1) {
                    array_pop($segments);
                }
                continue;
            }
            $segments[] = $segment;
        }

        $joined = implode('/', $segments);

        return str_starts_with($joined, '/') ? $joined : '/'.$joined;
    }

    private function metaContent(DOMXPath $xpath, string $name): ?string
    {
        $name = strtolower($name);
        $query = sprintf(
            '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="%s"]/@content',
            $name
        );
        $nodes = $xpath->query($query);
        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        return $this->normalizeText($nodes->item(0)?->nodeValue ?? '');
    }

    private function firstText(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        return $this->normalizeText($nodes->item(0)?->textContent ?? '');
    }

    private function normalizeText(string $text): ?string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return $text === '' ? null : $text;
    }
}
