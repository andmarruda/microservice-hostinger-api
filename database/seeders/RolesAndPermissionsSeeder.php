<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    private const ADMIN_PERMISSIONS = [
        'VPS.VirtualMachine.Manage.read',
        'Manage.Permissions.VPS.all',
        'Manage.Invite.user',
        'Manage.Users.create',
        'Manage.Users.delete',
        'Manage.Users.list',
        'Manage.VPS.access.grant',
        'Manage.VPS.access.revoke',
        'DNS.Zone.read',
        'Billing.getCatalog',
        'Orders.Subscriptions.read',
        'Domains.Portfolio.Manage.read',
        'Domains.Portfolio.Details',
        'Domains.Availability.validate',
        'Governance.manage',
        'Ops.view',
    ];

    private const USER_PERMISSIONS = [
        'VPS.VirtualMachine.Manage.read',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (array_unique(array_merge(self::ADMIN_PERMISSIONS, self::USER_PERMISSIONS)) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => self::GUARD]);
        $adminRole->syncPermissions(self::ADMIN_PERMISSIONS);

        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => self::GUARD]);
        $userRole->syncPermissions(self::USER_PERMISSIONS);
    }
}
