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
use App\Modules\SecurityResourceModule\UseCases\AddSshKey\AddSshKey;
use App\Modules\SecurityResourceModule\UseCases\RemoveSshKey\RemoveSshKey;
use App\Modules\VpsModule\Models\VpsProfile;
use App\Modules\VpsModule\Ports\Repositories\VpsRepositoryInterface;
use App\Modules\VpsModule\Ports\Services\HostingerApiClientInterface;
use App\Modules\VpsModule\UseCases\RebootVps\RebootVps;
use App\Modules\VpsModule\UseCases\StartVps\StartVps;
use App\Modules\VpsModule\UseCases\StopVps\StopVps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
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
        private AddSshKey $addSshKey,
        private RemoveSshKey $removeSshKey,
        private HostingerApiClientInterface $hostinger,
        private VpsRepositoryInterface $vpsRepository,
    ) {}

    public function index(Request $request): Response
    {
        $result = $this->getVpsList->execute($request->user());

        $vps = $result->success ? $this->withProfiles($result->data) : [];

        return Inertia::render('Vps/Index', [
            'vps'        => $vps,
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
        $sshKeys = $this->getVpsSshKeys->execute($user, $id);
        $vps = $details->success && $details->data ? $this->withProfile($details->data) : null;

        return Inertia::render('Vps/Show', [
            'vps'     => $vps,
            'metrics' => $metrics->success ? $metrics->data : [],
            'actions' => $actions->success ? $actions->data : [],
            'backups' => $backups->success ? $backups->data : [],
            'sshKeys' => $sshKeys->success ? $sshKeys->data : [],
            'vpsId'   => $id,
        ]);
    }

    public function updateName(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
        ]);

        VpsProfile::updateOrCreate(
            ['vps_id' => $id],
            [
                'display_name' => $validated['display_name'],
                'updated_by' => $request->user()->id,
            ],
        );

        return back()->with('success', 'VPS name saved.');
    }

    public function updatePassword(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!$this->vpsRepository->userHasAccess($request->user()->id, $id)) {
            abort(403);
        }

        $result = $this->hostinger->changePassword($id, $validated['password'], (string) Str::uuid());

        if (!$result->success) {
            return back()->with('error', 'Failed to change VPS password.');
        }

        return back()->with('success', 'VPS password change initiated.');
    }

    public function storeSshKey(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'key_name' => ['required', 'string', 'max:255'],
            'public_key' => ['required', 'string'],
        ]);

        $user = $request->user();
        $result = $this->addSshKey->execute(
            userId: $user->id,
            vpsId: $id,
            keyName: $validated['key_name'],
            publicKey: $validated['public_key'],
            actorEmail: $user->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden' => 'You do not have access to this VPS.',
                'policy_denied' => 'Action blocked by policy: ' . ($result->policyReason ?? ''),
                'invalid_key' => $result->validationMessage ?? 'Invalid SSH key.',
                default => 'Failed to add SSH key.',
            });
        }

        return back()->with('success', 'SSH key added.');
    }

    public function destroySshKey(Request $request, string $id, string $keyId): RedirectResponse
    {
        $user = $request->user();
        $result = $this->removeSshKey->execute(
            userId: $user->id,
            vpsId: $id,
            keyId: $keyId,
            actorEmail: $user->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return back()->with('error', match ($result->error) {
                'forbidden' => 'You do not have access to this VPS.',
                'policy_denied' => 'Action blocked by policy: ' . ($result->policyReason ?? ''),
                default => 'Failed to remove SSH key.',
            });
        }

        return back()->with('success', 'SSH key removed.');
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

    private function withProfiles(array $vpsList): array
    {
        $profiles = VpsProfile::whereIn('vps_id', array_column($vpsList, 'id'))
            ->pluck('display_name', 'vps_id');

        return array_map(fn (array $vps) => $this->withProfile($vps, $profiles[$vps['id']] ?? null), $vpsList);
    }

    private function withProfile(array $vps, ?string $displayName = null): array
    {
        $displayName ??= VpsProfile::where('vps_id', $vps['id'] ?? '')->value('display_name');

        return array_merge($vps, [
            'display_name' => $displayName ?: ($vps['hostname'] ?? $vps['id'] ?? 'VPS'),
        ]);
    }
}
