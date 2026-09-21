<?php

namespace App\Routing;

use App\Shared\Responses\JsonEncoding;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\ResponseFactory;

class UnicodeJsonResponseFactory extends ResponseFactory
{
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        $headers = array_merge([
            'Content-Type' => 'application/json; charset=UTF-8',
        ], $headers);

        return new JsonResponse(
            $data,
            $status,
            $headers,
            $options | JsonEncoding::OPTIONS
        );
    }
}
