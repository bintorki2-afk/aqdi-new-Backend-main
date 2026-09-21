<?php

namespace App\Modules\Contracts\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Support\DeedImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves deed/instrument images behind a temporary signed URL (see {@see DeedImage}).
 * The `signed` middleware on the route guarantees the URL was minted by the backend
 * and has not expired, so the file is never exposed from a public storage path.
 */
class DeedImageController extends Controller
{
    public function show(Contract $contract, string $field): StreamedResponse
    {
        abort_unless(DeedImage::isField($field), 404);

        $raw = $contract->getAttributes()[$field] ?? null;
        $resolved = DeedImage::resolveStored(is_string($raw) ? $raw : null);

        abort_if($resolved === null, 404);

        [$disk, $path] = $resolved;

        return Storage::disk($disk)->response($path);
    }
}
