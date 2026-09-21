<?php

namespace App\Support;

final class BlogHtmlSanitizer
{
    /**
     * Tags the public blog renderer is allowed to keep (dangerouslySetInnerHTML).
     *
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'ul', 'ol', 'li', 'blockquote',
        'a', 'strong', 'em', 'b', 'i',
        'img', 'figure', 'figcaption', 'br',
    ];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $html = (string) preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#is', '', $html);
        $html = (string) preg_replace('#<(script|style|iframe|object|embed|form)[^>]*\/?>#is', '', $html);

        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $html = strip_tags($html, $allowed);

        $html = (string) preg_replace('/\s(on\w+|style|class|id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html);
        $html = (string) preg_replace('/javascript\s*:/iu', '', $html);
        $html = (string) preg_replace('/data\s*:/iu', '', $html);

        return $html;
    }

    public static function plainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public static function excerpt(?string $html, int $length = 160): string
    {
        $plain = self::plainText($html);

        if ($plain === '') {
            return '';
        }

        if (mb_strlen($plain) <= $length) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, $length), " \t\n\r\0\x0B.,،؛:").'…';
    }

    public static function wordCount(?string $html): int
    {
        $plain = self::plainText($html);

        if ($plain === '') {
            return 0;
        }

        $parts = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? count($parts) : 0;
    }
}
