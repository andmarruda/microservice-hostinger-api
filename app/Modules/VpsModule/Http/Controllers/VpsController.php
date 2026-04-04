<?php

namespace App\Modules\VpsModule\Http\Controllers;

use App\Modules\VpsModule\UseCases\RebootVps\RebootVps;
use App\Modules\VpsModule\UseCases\StartVps\StartVps;
use App\Modules\VpsModule\UseCases\StopVps\StopVps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VpsController extends Controller
{
    public function __construct(
        private StartVps $startVps,
        private StopVps $stopVps,
        private RebootVps $rebootVps,
    ) {}

    public function start(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->startVps->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'vps_not_found' => response()->json(['message' => 'VPS not found.'], 404),
                'hostinger_error' => response()->json([
                    'message' => 'Failed to communicate with Hostinger.',
                    'correlation_id' => $result->correlationId,
                ], 502),
            };
        }

        return response()->json([
            'data' => [
                'vps_id' => $vpsId,
                'correlation_id' => $result->correlationId,
            ],
        ]);
    }

    public function stop(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->stopVps->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'vps_not_found' => response()->json(['message' => 'VPS not found.'], 404),
                'hostinger_error' => response()->json([
                    'message' => 'Failed to communicate with Hostinger.',
                    'correlation_id' => $result->correlationId,
                ], 502),
            };
        }

        return response()->json([
            'data' => [
                'vps_id' => $vpsId,
                'correlation_id' => $result->correlationId,
            ],
        ]);
    }

    public function reboot(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->rebootVps->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'vps_not_found' => response()->json(['message' => 'VPS not found.'], 404),
                'hostinger_error' => response()->json([
                    'message' => 'Failed to communicate with Hostinger.',
                    'correlation_id' => $result->correlationId,
                ], 502),
            };
        }

        return response()->json([
            'data' => [
                'vps_id' => $vpsId,
                'correlation_id' => $result->correlationId,
            ],
        ]);
    }
}
