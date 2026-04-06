<?php

namespace App\Modules\PolicyModule\Http\Controllers;

use App\Modules\PolicyModule\Models\EnforcementPolicy;
use App\Modules\PolicyModule\PolicyActions;
use App\Modules\PolicyModule\UseCases\CreatePolicy\CreatePolicy;
use App\Modules\PolicyModule\UseCases\DeletePolicy\DeletePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PolicyController extends Controller
{
    public function __construct(
        private CreatePolicy $createPolicy,
        private DeletePolicy $deletePolicy,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$request->user()->hasRole('root')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $policies = EnforcementPolicy::with('creator:id,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $policies]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'action'       => ['required', 'string', 'in:' . implode(',', PolicyActions::ALL)],
            'scope_type'   => ['required', 'string', 'in:global,vps,role,user'],
            'scope_id'     => ['nullable', 'string'],
            'reason'       => ['nullable', 'string', 'max:500'],
            'active_from'  => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
        ]);

        $result = $this->createPolicy->execute($request->user(), $validated);

        if (!$result->success) {
            return match ($result->error) {
                'forbidden'      => response()->json(['message' => 'Forbidden.'], 403),
                'invalid_action' => response()->json(['message' => 'Invalid policy action.'], 422),
                default          => response()->json(['message' => 'Unexpected error.'], 500),
            };
        }

        return response()->json(['data' => ['id' => $result->policyId]], 201);
    }

    public function destroy(Request $request, int $policyId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->deletePolicy->execute($request->user(), $policyId);

        if (!$result->success) {
            return match ($result->error) {
                'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
                'not_found' => response()->json(['message' => 'Policy not found.'], 404),
                default     => response()->json(['message' => 'Unexpected error.'], 500),
            };
        }

        return response()->json(['data' => ['deleted' => true]]);
    }
}
