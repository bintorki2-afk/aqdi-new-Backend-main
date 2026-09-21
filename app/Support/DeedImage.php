<?php

namespace App\Support;

use App\Models\Contract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Deed / instrument images must NOT be world-readable from the public disk.
 *
 * New uploads go to the private disk ({@see self::DISK}); reads happen only through
 * a temporary signed route ({@see routes} `contracts.deed-image`). Legacy files that
 * still live on the public disk are streamed transparently for backward compatibility.
 */
final class DeedImage
{
    /** Private disk for new deed uploads. */
    public const DISK = 'local';

    /** Sub-directory (relative to the disk root) new deed images are stored under. */
    public const DIR = 'contracts/deeds';

    /** Signed-URL lifetime. */
    public const TTL_MINUTES = 30;

    /** Contract columns that hold deed/instrument images. */
    public const FIELDS = [
        'image_instrument',
        'image_instrument_from_the_front',
        'image_instrument_from_the_back',
    ];

    public static function isField(string $field): bool
    {
        return in_array($field, self::FIELDS, true);
    }

    /**
     * Temporary signed URL to fetch the deed image for a contract field, or null.
     */
    public static function signedUrl(Contract $contract, string $field): ?string
    {
        if (! self::isField($field)) {
            return null;
        }

        $raw = $contract->getAttributes()[$field] ?? $contract->{$field} ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        return URL::temporarySignedRoute(
            'contracts.deed-image',
            now()->addMinutes(self::TTL_MINUTES),
            ['contract' => $contract->getKey(), 'field' => $field]
        );
    }

    /**
     * Resolve [disk, path] for a stored deed value, trying the private disk first and
     * falling back to the legacy public disk. Returns null when nothing is found.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function resolveStored(?string $raw): ?array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $path = ltrim(trim($raw), '/');
        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return [self::DISK, $path];
        }

        if (Storage::disk('public')->exists($path)) {
            return ['public', $path];
        }

        return null;
    }
}
