<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\ContentPageService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContentPageController extends Controller
{
    use Responser;

    public function __construct(private readonly ContentPageService $contentPages)
    {
    }

    public function show(string $pageKey)
    {
        try {
            $normalizedKey = $this->contentPages->normalizePageKey($pageKey);

            return $this->apiResponse(
                $this->contentPages->show($normalizedKey),
                $this->contentPages->fetchMessageFor($normalizedKey)
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function upsert(Request $request, string $pageKey)
    {
        try {
            $normalizedKey = $this->contentPages->normalizePageKey($pageKey);

            return $this->apiResponse(
                $this->contentPages->upsert($request, $normalizedKey),
                $this->contentPages->saveMessageFor($normalizedKey)
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
