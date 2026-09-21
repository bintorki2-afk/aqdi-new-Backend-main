<?php

namespace App\Shared\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait Responser
{
    protected function jsonResponse(array $payload, int $http = 200): JsonResponse
    {
        return response()->json($payload, $http, [], JsonEncoding::OPTIONS);
    }

    protected function successMessage($msg, $code = 200)
    {
        return $this->jsonResponse([
            'message' => $msg,
            'code' => $code,
            'success' => true,
        ], $this->httpStatus($code));
    }

    protected function errorMessage($msg, $code = 400)
    {
        $http = $this->httpStatus($code);

        return $this->jsonResponse([
            'message' => $msg,
            'code' => $http === 403 ? 'forbidden' : $code,
            'success' => false,
        ], $http);
    }

    protected function errorResponse($data, $code)
    {
        $http = $this->httpStatus($code);

        return $this->jsonResponse([
            'code' => $code,
            'success' => false,
            'errors' => $data,
        ], $http);
    }

    protected function apiResponse($data, $msg, $code = 200, ?int $httpCode = null)
    {
        // Legacy call shape `apiResponse($data, $msg, false, 404)` used by several admin
        // controllers: the bool landed in $code and every error went out as HTTP 200 / success:true.
        $success = true;
        if (is_bool($code)) {
            $success = $code;
            $code = $httpCode ?? ($success ? 200 : 400);
        }

        return $this->jsonResponse([
            'message' => $msg,
            'code' => $code,
            'success' => $success,
            'data' => $data,
        ], $this->httpStatus((int) $code));
    }

    protected function paginate(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    protected function perPageFromRequest(Request $request, int $default = 20, int $max = 100): int
    {
        return min(max((int) $request->input('per_page', $default), 1), $max);
    }

    /**
     * Standard admin list response: data.items + data.pagination.
     *
     * @param  mixed  $items  Resource collection or list payload
     * @param  array<string, mixed>  $meta  Extra keys merged into data (before items/pagination)
     */
    protected function paginatedApiResponse(
        LengthAwarePaginator $paginator,
        mixed $items,
        ?string $message = null,
        array $meta = [],
        int $code = 200
    ): JsonResponse {
        $message = $message ?? trans('api.success');

        $data = array_merge($meta, [
            'items' => $items,
            'pagination' => $this->paginate($paginator),
        ]);

        return $this->apiResponse($data, $message, $code);
    }

    private function httpStatus(int $code): int
    {
        return ($code >= 100 && $code < 600) ? $code : 200;
    }
}
