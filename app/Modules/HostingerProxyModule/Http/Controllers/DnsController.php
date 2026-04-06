<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetDnsSnapshots\GetDnsSnapshots;
use App\Modules\HostingerProxyModule\UseCases\GetDnsZone\GetDnsZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DnsController extends Controller
{
    public function __construct(
        private GetDnsZone $getDnsZone,
        private GetDnsSnapshots $getDnsSnapshots,
    ) {}

    public function zone(Request $request, string $domain): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getDnsZone->execute($request->user(), $domain);

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    public function snapshots(Request $request, string $domain): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getDnsSnapshots->execute($request->user(), $domain);

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
