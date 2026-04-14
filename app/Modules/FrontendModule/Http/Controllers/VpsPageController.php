<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetVpsActions\GetVpsActions;
use App\Modules\HostingerProxyModule\UseCases\GetVpsBackups\GetVpsBackups;
use App\Modules\HostingerProxyModule\UseCases\GetVpsDetails\GetVpsDetails;
use App\Modules\HostingerProxyModule\UseCases\GetVpsFirewall\GetVpsFirewall;
use App\Modules\HostingerProxyModule\UseCases\GetVpsList\GetVpsList;
use App\Modules\HostingerProxyModule\UseCases\GetVpsMetrics\GetVpsMetrics;
use App\Modules\HostingerProxyModule\UseCases\GetVpsSnapshots\GetVpsSnapshots;
use App\Modules\HostingerProxyModule\UseCases\GetVpsSshKeys\GetVpsSshKeys;
use App\Modules\VpsModule\UseCases\RebootVps\RebootVps;
use App\Modules\VpsModule\UseCases\StartVps\StartVps;
use App\Modules\VpsModule\UseCases\StopVps\StopVps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class VpsPageController extends Controller
{
    public function __construct(
        private GetVpsList $getVpsList,
        private GetVpsDetails $getVpsDetails,
        private GetVpsFirewall $getVpsFirewall,
        private GetVpsSshKeys $getVpsSshKeys,
        private GetVpsSnapshots $getVpsSnapshots,
        private GetVpsMetrics $getVpsMetrics,
        private GetVpsActions $getVpsActions,
        private GetVpsBackups $getVpsBackups,
        private StartVps $startVps,
        private StopVps $stopVps,
        private RebootVps $rebootVps,
    ) {}

    public function index(Request $request): Response
    {
        $result = $this->getVpsList->execute($request->user());

        return Inertia::render('Vps/Index', [
            'vps'        => $result->success ? $result->data : [],
            'canSeeAll'  => $request->user()->can('Manage.Permissions.VPS.all'),
            'error'      => !$result->success ? $result->error : null,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $user    = $request->user();
        $details = $this->getVpsDetails->execute($user, $id);
        $metrics = $this->getVpsMetrics->execute($user, $id);
        $actions = $this->getVpsActions->execute($user, $id);
        $backups = $this->getVpsBackups->execute($user, $id);

        return Inertia::render('Vps/Show', [
            'vps'     => $details->success ? $details->data : null,
            'metrics' => $metrics->success ? $metrics->data : [],
            'actions' => $actions->success ? $actions->data : [],
            'backups' => $backups->success ? $backups->data : [],
            'vpsId'   => $id,
        ]);
    }

    public function firewall(Request $request, string $id): Response
    {
        $result = $this->getVpsFirewall->execute($request->user(), $id);

        return Inertia::render('Vps/Firewall', [
            'rules' => $result->success ? $result->data : [],
            'vpsId' => $id,
        ]);
    }

    public function sshKeys(Request $request, string $id): Response
    {
        $result = $this->getVpsSshKeys->execute($request->user(), $id);

        return Inertia::render('Vps/SshKeys', [
            'keys'  => $result->success ? $result->data : [],
            'vpsId' => $id,
        ]);
    }

    public function snapshots(Request $request, string $id): Response
    {
        $result = $this->getVpsSnapshots->execute($request->user(), $id);

        return Inertia::render('Vps/Snapshots', [
            'snapshots' => $result->success ? $result->data : [],
            'vpsId'     => $id,
        ]);
    }

    public function start(Request $request, string $id): RedirectResponse
    {
        $user   = $request->user();
        $result = $this->startVps->execute($user->id, $id, $user->email, $request->ip(), $request->userAgent());

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden'     => 'You do not have access to this VPS.',
                'policy_denied' => 'Action blocked by policy: ' . ($result->policyReason ?? ''),
                default         => 'Failed to start VPS.',
            });
        }

        return back()->with('success', 'VPS start initiated.');
    }

    public function stop(Request $request, string $id): RedirectResponse
    {
        $user   = $request->user();
        $result = $this->stopVps->execute($user->id, $id, $user->email, $request->ip(), $request->userAgent());

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden'     => 'You do not have access to this VPS.',
                'policy_denied' => 'Action blocked by policy: ' . ($result->policyReason ?? ''),
                default         => 'Failed to stop VPS.',
            });
        }

        return back()->with('success', 'VPS stop initiated.');
    }

    public function reboot(Request $request, string $id): RedirectResponse
    {
        $user   = $request->user();
        $result = $this->rebootVps->execute($user->id, $id, $user->email, $request->ip(), $request->userAgent());

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden'     => 'You do not have access to this VPS.',
                'policy_denied' => 'Action blocked by policy: ' . ($result->policyReason ?? ''),
                default         => 'Failed to reboot VPS.',
            });
        }

        return back()->with('success', 'VPS reboot initiated.');
    }
}
