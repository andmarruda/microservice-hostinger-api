<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetDomainAvailability\GetDomainAvailability;
use App\Modules\HostingerProxyModule\UseCases\GetDomainForwarding\GetDomainForwarding;
use App\Modules\HostingerProxyModule\UseCases\GetDomainPortfolio\GetDomainPortfolio;
use App\Modules\HostingerProxyModule\UseCases\GetWhois\GetWhois;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DomainsController extends Controller
{
    public function __construct(
        private GetDomainAvailability $getDomainAvailability,
        private GetDomainForwarding $getDomainForwarding,
        private GetDomainPortfolio $getDomainPortfolio,
        private GetWhois $getWhois,
    ) {}

    public function availability(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate(['domain' => 'required|string']);

        $result = $this->getDomainAvailability->execute($request->user(), $request->input('domain'));

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    public function forwarding(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getDomainForwarding->execute($request->user());

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    public function portfolio(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getDomainPortfolio->execute($request->user());

        if (!$result->success) {
            return $this->errorResponse($result->error);
        }

        return response()->json(['data' => $result->data]);
    }

    public function whois(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getWhois->execute($request->user());

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
