<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetHostingDatacenters\GetHostingDatacenters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HostingController extends Controller
{
    public function __construct(
        private GetHostingDatacenters $getHostingDatacenters,
    ) {}

    public function datacenters(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getHostingDatacenters->execute($request->user());

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                default => response()->json(['message' => 'Failed to communicate with Hostinger.'], 502),
            };
        }

        return response()->json(['data' => $result->data]);
    }
}
