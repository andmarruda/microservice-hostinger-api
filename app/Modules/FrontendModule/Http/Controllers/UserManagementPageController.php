<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\UseCases\InviteUser\InviteUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class UserManagementPageController extends Controller
{
    public function __construct(
        private InviteUser $inviteUser,
    ) {}

    public function index(): Response
    {
        $users = User::orderBy('created_at', 'desc')
            ->get(['id', 'name', 'email', 'email_verified_at', 'is_manager', 'created_at']);

        return Inertia::render('Users/Index', [
            'users' => $users,
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
            'is_manager'            => ['boolean'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'is_manager'        => $validated['is_manager'] ?? false,
        ]);

        if ($validated['is_manager'] ?? false) {
            $permission = Permission::firstOrCreate(['name' => 'Manage.Invite.user', 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return back()->with('success', "User {$validated['name']} created successfully.");
    }
}
