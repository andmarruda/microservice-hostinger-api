<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    public static function paginated(mixed $items, array $meta): JsonResponse
    {
        return response()->json(['data' => $items, 'meta' => $meta]);
    }

    public static function error(
        string $message,
        int $status,
        ?array $errors = null,
        array $extra = [],
    ): JsonResponse {
        $body = array_merge(['message' => $message], $extra);

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
