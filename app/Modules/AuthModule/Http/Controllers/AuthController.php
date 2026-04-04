<?php

namespace App\Modules\AuthModule\Http\Controllers;

use App\Modules\AuthModule\UseCases\LoginUser\LoginUser;
use App\Modules\AuthModule\UseCases\LogoutUser\LogoutUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function __construct(
        private LoginUser $loginUser,
        private LogoutUser $logoutUser,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_mode' => ['sometimes', 'boolean'],
        ]);

        $issueToken = (bool) ($validated['token_mode'] ?? false);

        $result = $this->loginUser->execute(
            email: $validated['email'],
            password: $validated['password'],
            issueToken: $issueToken,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $response = [
            'data' => [
                'id' => $result->user->id,
                'name' => $result->user->name,
                'email' => $result->user->email,
            ],
        ];

        if ($result->token) {
            $response['data']['token'] = $result->token;
        }

        return response()->json($response);
    }

    public function logout(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $revokeToken = $request->bearerToken() !== null;

        $this->logoutUser->execute(
            user: $request->user(),
            revokeToken: $revokeToken,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json(['data' => ['message' => 'Logged out successfully.']]);
    }

    public function me(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }
}
