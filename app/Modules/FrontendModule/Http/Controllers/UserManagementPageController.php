<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\AuthModule\Infrastructure\Mail\WelcomeMail;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\UseCases\InviteUser\InviteUser;
use App\Modules\HostingerProxyModule\UseCases\GetVpsList\GetVpsList;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementPageController extends Controller
{
    public function __construct(
        private InviteUser $inviteUser,
        private GetVpsList $getVpsList,
    ) {}

    public function index(): Response
    {
        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'email', 'email_verified_at', 'is_manager', 'created_at'])
            ->map(fn ($u) => array_merge($u->toArray(), [
                'role' => $u->roles->first()?->name ?? 'user',
            ]));

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::with(['roles'])->findOrFail($id);

        $allVpsResult = $this->getVpsList->execute($request->user());
        $allVps = $allVpsResult->success ? $allVpsResult->data : [];

        $grants = VpsAccessGrant::where('user_id', $id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get(['id', 'vps_id', 'granted_at', 'expires_at']);

        $grantedVpsIds = $grants->pluck('vps_id')->all();

        $permissions = SecurityPermission::where('user_id', $id)
            ->whereIn('vps_id', $grantedVpsIds)
            ->get(['vps_id', 'can_manage_firewall', 'can_manage_ssh_keys', 'can_manage_snapshots']);

        $permissionsByVps = $permissions->keyBy('vps_id');

        $grantedVps = array_values(array_filter(
            $allVps,
            fn ($v) => in_array($v['id'] ?? null, $grantedVpsIds, true)
        ));

        $grantedVps = array_map(function ($vps) use ($grants, $permissionsByVps) {
            $grant = $grants->firstWhere('vps_id', $vps['id']);
            $perms = $permissionsByVps[$vps['id']] ?? null;
            return array_merge($vps, [
                'grant_id'             => $grant?->id,
                'granted_at'           => $grant?->granted_at,
                'expires_at'           => $grant?->expires_at,
                'can_manage_firewall'  => (bool) ($perms?->can_manage_firewall ?? false),
                'can_manage_ssh_keys'  => (bool) ($perms?->can_manage_ssh_keys ?? false),
                'can_manage_snapshots' => (bool) ($perms?->can_manage_snapshots ?? false),
            ]);
        }, $grantedVps);

        $availableVps = array_values(array_filter(
            $allVps,
            fn ($v) => !in_array($v['id'] ?? null, $grantedVpsIds, true)
        ));

        return Inertia::render('Users/Show', [
            'user'         => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at'        => $user->created_at,
                'role'              => $user->roles->first()?->name ?? 'user',
            ],
            'grantedVps'   => $grantedVps,
            'availableVps' => $availableVps,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $result = $this->inviteUser->execute(
            inviter: $request->user(),
            email: $validated['email'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (! $result->success) {
            return back()->withErrors(['email' => match ($result->error) {
                'forbidden'                => 'You do not have permission to invite users.',
                'email_already_registered' => 'This email is already registered.',
                default                    => 'Failed to send invitation.',
            }]);
        }

        return back()->with('success', "Invitation sent to {$validated['email']}.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'role'                  => ['required', 'in:admin,user'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'is_manager'        => $validated['role'] === 'admin',
        ]);

        $user->assignRole($validated['role']);

        Mail::to($user->email)->send(new WelcomeMail(
            name: $user->name,
            email: $user->email,
            temporaryPassword: $validated['password'],
            loginUrl: route('login'),
        ));

        return back()->with('success', "User {$validated['name']} created successfully.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['user' => 'Cannot delete the last admin.']);
            }
        }

        VpsAccessGrant::where('user_id', $id)->delete();
        SecurityPermission::where('user_id', $id)->delete();
        $user->delete();

        return redirect()->route('users.index')->with('success', "User {$user->name} deleted.");
    }
}
