<?php

namespace App\Modules\SecurityResourceModule\Http\Controllers;

use App\Modules\SecurityResourceModule\UseCases\AddSshKey\AddSshKey;
use App\Modules\SecurityResourceModule\UseCases\RemoveSshKey\RemoveSshKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SshKeyController extends Controller
{
    public function __construct(
        private AddSshKey $addSshKey,
        private RemoveSshKey $removeSshKey,
    ) {}

    public function store(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'key_name' => ['required', 'string', 'max:255'],
            'public_key' => ['required', 'string'],
        ]);

        $result = $this->addSshKey->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            keyName: $validated['key_name'],
            publicKey: $validated['public_key'],
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'invalid_key' => response()->json(['message' => $result->validationMessage], 422),
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

    public function destroy(Request $request, string $vpsId, string $keyId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'confirm_destructive' => ['required', 'accepted'],
        ]);

        $result = $this->removeSshKey->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            keyId: $keyId,
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
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
