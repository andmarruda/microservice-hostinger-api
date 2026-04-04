<?php

namespace App\Modules\SecurityResourceModule\Http\Controllers;

use App\Modules\SecurityResourceModule\UseCases\AddFirewallRule\AddFirewallRule;
use App\Modules\SecurityResourceModule\UseCases\RemoveFirewallRule\RemoveFirewallRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FirewallController extends Controller
{
    public function __construct(
        private AddFirewallRule $addFirewallRule,
        private RemoveFirewallRule $removeFirewallRule,
    ) {}

    public function store(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'protocol' => ['required', 'string'],
            'port' => ['required'],
            'source' => ['nullable', 'string'],
        ]);

        $result = $this->addFirewallRule->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            rule: $validated,
            actorEmail: $request->user()->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'invalid_rule' => response()->json(['message' => $result->validationMessage], 422),
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

    public function destroy(Request $request, string $vpsId, string $ruleId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'confirm_destructive' => ['required', 'accepted'],
        ]);

        $result = $this->removeFirewallRule->execute(
            userId: $request->user()->id,
            vpsId: $vpsId,
            ruleId: $ruleId,
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
