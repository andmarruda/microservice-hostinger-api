<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetBillingCatalog\GetBillingCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BillingController extends Controller
{
    public function __construct(
        private GetBillingCatalog $getBillingCatalog,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getBillingCatalog->execute($request->user());

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
