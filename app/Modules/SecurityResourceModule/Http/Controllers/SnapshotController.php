<?php

namespace App\Modules\SecurityResourceModule\Http\Controllers;

use App\Modules\SecurityResourceModule\UseCases\CreateSnapshot\CreateSnapshot;
use App\Modules\SecurityResourceModule\UseCases\DeleteSnapshot\DeleteSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SnapshotController extends Controller
{
    public function __construct(
        private CreateSnapshot $createSnapshot,
        private DeleteSnapshot $deleteSnapshot,
    ) {}

    public function store(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->createSnapshot->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            label: $validated['label'],
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'policy_denied' => response()->json(['message' => 'Operation denied by policy.', 'reason' => $result->policyReason], 403),
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
        ], 201);
    }

    public function destroy(Request $request, string $vpsId, string $snapshotId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'confirm_destructive' => ['required', 'accepted'],
        ]);

        $result = $this->deleteSnapshot->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            snapshotId: $snapshotId,
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'policy_denied' => response()->json(['message' => 'Operation denied by policy.', 'reason' => $result->policyReason], 403),
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
