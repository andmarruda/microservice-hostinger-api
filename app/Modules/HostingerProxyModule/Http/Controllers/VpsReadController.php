<?php

namespace App\Modules\HostingerProxyModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetVpsActions\GetVpsActions;
use App\Modules\HostingerProxyModule\UseCases\GetVpsBackups\GetVpsBackups;
use App\Modules\HostingerProxyModule\UseCases\GetVpsDatacenters\GetVpsDatacenters;
use App\Modules\HostingerProxyModule\UseCases\GetVpsDetails\GetVpsDetails;
use App\Modules\HostingerProxyModule\UseCases\GetVpsFirewall\GetVpsFirewall;
use App\Modules\HostingerProxyModule\UseCases\GetVpsList\GetVpsList;
use App\Modules\HostingerProxyModule\UseCases\GetVpsMetrics\GetVpsMetrics;
use App\Modules\HostingerProxyModule\UseCases\GetVpsOsTemplates\GetVpsOsTemplates;
use App\Modules\HostingerProxyModule\UseCases\GetVpsPostInstallScripts\GetVpsPostInstallScripts;
use App\Modules\HostingerProxyModule\UseCases\GetVpsSshKeys\GetVpsSshKeys;
use App\Modules\HostingerProxyModule\UseCases\GetVpsSnapshots\GetVpsSnapshots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VpsReadController extends Controller
{
    public function __construct(
        private GetVpsList $getVpsList,
        private GetVpsDetails $getVpsDetails,
        private GetVpsMetrics $getVpsMetrics,
        private GetVpsActions $getVpsActions,
        private GetVpsBackups $getVpsBackups,
        private GetVpsFirewall $getVpsFirewall,
        private GetVpsOsTemplates $getVpsOsTemplates,
        private GetVpsSshKeys $getVpsSshKeys,
        private GetVpsSnapshots $getVpsSnapshots,
        private GetVpsDatacenters $getVpsDatacenters,
        private GetVpsPostInstallScripts $getVpsPostInstallScripts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsList->execute($request->user());

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function show(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsDetails->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function metrics(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsMetrics->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function actions(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsActions->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function backups(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsBackups->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function firewall(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsFirewall->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function osTemplates(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsOsTemplates->execute($request->user());

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function sshKeys(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsSshKeys->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function snapshots(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsSnapshots->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function datacenters(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsDatacenters->execute($request->user());

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    public function postInstallScripts(Request $request, string $vpsId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $result = $this->getVpsPostInstallScripts->execute($request->user(), $vpsId);

        return $result->success
            ? response()->json(['data' => $result->data])
            : $this->errorResponse($result->error);
    }

    private function errorResponse(?string $error): JsonResponse
    {
        return match ($error) {
            'forbidden' => response()->json(['message' => 'Forbidden.'], 403),
            default => response()->json(['message' => 'Failed to communicate with Hostinger.'], 502),
        };
    }
}
