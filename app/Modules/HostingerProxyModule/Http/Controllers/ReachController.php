<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetReachContacts\GetReachContacts;
use App\Modules\HostingerProxyModule\UseCases\GetReachSegments\GetReachSegments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReachController extends Controller
{
    public function __construct(
        private GetReachContacts $getReachContacts,
        private GetReachSegments $getReachSegments,
    ) {}

    public function contacts(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getReachContacts->execute($request->user());

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    public function segments(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getReachSegments->execute($request->user());

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    private function errorResponse(?string $error): JsonResponse
    {
        return match ($error) {
            'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
            default => response()->json(['message' => 'Failed to communicate with Hostinger.'], 502),
        };
    }
}
