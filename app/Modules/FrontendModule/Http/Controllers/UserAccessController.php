<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\AuthModule\Models\User;
use App\Modules\SecurityResourceModule\Models\SecurityPermission;
use App\Modules\VpsModule\Models\VpsAccessGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserAccessController extends Controller
{
    public function grant(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'vps_id'               => ['required', 'string'],
            'can_manage_firewall'  => ['boolean'],
            'can_manage_ssh_keys'  => ['boolean'],
            'can_manage_snapshots' => ['boolean'],
        ]);

        User::findOrFail($id);

        VpsAccessGrant::updateOrCreate(
            ['user_id' => $id, 'vps_id' => $validated['vps_id']],
            ['granted_by' => $request->user()->id, 'granted_at' => now()],
        );

        SecurityPermission::updateOrCreate(
            ['user_id' => $id, 'vps_id' => $validated['vps_id']],
            [
                'granted_by'           => $request->user()->id,
                'can_manage_firewall'  => $validated['can_manage_firewall'] ?? false,
                'can_manage_ssh_keys'  => $validated['can_manage_ssh_keys'] ?? false,
                'can_manage_snapshots' => $validated['can_manage_snapshots'] ?? false,
            ]
        );

        return back()->with('success', 'VPS access granted.');
    }

    public function revoke(int $id, string $vpsId): RedirectResponse
    {
        User::findOrFail($id);

        VpsAccessGrant::where('user_id', $id)->where('vps_id', $vpsId)->delete();
        SecurityPermission::where('user_id', $id)->where('vps_id', $vpsId)->delete();

        return back()->with('success', 'VPS access revoked.');
    }

    public function updatePermissions(Request $request, int $id, string $vpsId): RedirectResponse
    {
        $validated = $request->validate([
            'can_manage_firewall'  => ['boolean'],
            'can_manage_ssh_keys'  => ['boolean'],
            'can_manage_snapshots' => ['boolean'],
        ]);

        User::findOrFail($id);

        SecurityPermission::updateOrCreate(
            ['user_id' => $id, 'vps_id' => $vpsId],
            array_merge(
                ['granted_by' => $request->user()->id],
                $validated
            )
        );

        return back()->with('success', 'Permissions updated.');
    }
}
