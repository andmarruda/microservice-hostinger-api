<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetPaymentMethods\GetPaymentMethods;
use App\Modules\HostingerProxyModule\UseCases\GetSubscriptions\GetSubscriptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrdersController extends Controller
{
    public function __construct(
        private GetPaymentMethods $getPaymentMethods,
        private GetSubscriptions $getSubscriptions,
    ) {}

    public function paymentMethods(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getPaymentMethods->execute($request->user());

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getSubscriptions->execute($request->user());

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
