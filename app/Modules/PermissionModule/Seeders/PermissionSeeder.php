<?php

namespace App\Modules\PermissionModule\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        // Billing
        'Billing.getCatalog',

        // Orders
        'Orders.PaymentMethods.create',
        'Orders.PaymentMethods.read',
        'Orders.PaymentMethods.delete',
        'Orders.Subscriptions.read',
        'Orders.Subscriptions.update',
        'Orders.Subscriptions.delete',

        // Domains
        'Domains.Availability.validate',
        'Domains.Forwarding.read',
        'Domains.Forwarding.create',
        'Domains.Forwarding.delete',
        'Domains.Portfolio.DomainLock.update',
        'Domains.Portfolio.DomainLock.delete',
        'Domains.Portfolio.Details',
        'Domains.Portfolio.Manage.create',
        'Domains.Portfolio.Manage.read',
        'Domains.Portfolio.Privacy.update',
        'Domains.Portfolio.Privacy.delete',
        'Domains.Portfolio.Nameservers.update',
        'Domains.Whois.read',
        'Domains.Whois.list',
        'Domains.Whois.create',
        'Domains.Whois.delete',
        'Domains.Whois.usage',
        'Domains.AccessVerifier.read',

        // DNS
        'DNS.Snapshot.read',
        'DNS.Snapshot.list',
        'DNS.Snapshot.restore',
        'DNS.Zone.read',
        'DNS.Zone.update',
        'DNS.Zone.delete',
        'DNS.Zone.reset',
        'DNS.Zone.validate',

        // Hosting
        'Hosting.Datacenters.list',
        'Hosting.Domains.Subdomain.create',
        'Hosting.Domains.Subdomain.verify',

        // Reach
        'Reach.Contacts.read',
        'Reach.Contacts.create',
        'Reach.Contacts.delete',
        'Reach.Segments.list',
        'Reach.Segments.create',
        'Reach.Segments.details',

        // VPS Actions
        'VPS.Actions.read',
        'VPS.Actions.details',

        // VPS Backups
        'VPS.Backups.read',
        'VPS.Backups.restore',

        // VPS DataCenters
        'VPS.DataCenters.list',

        // VPS Firewall
        'VPS.Firewall.read',
        'VPS.Firewall.create',
        'VPS.Firewall.update',
        'VPS.Firewall.delete',

        // VPS OS Templates
        'VPS.OSTemplates.read',
        'VPS.OSTemplates.details',

        // VPS Post-Install Scripts
        'VPS.PostInstallScripts.read',
        'VPS.PostInstallScripts.create',
        'VPS.PostInstallScripts.update',
        'VPS.PostInstallScripts.delete',

        // VPS Public Keys
        'VPS.PublicKeys.read',
        'VPS.PublicKeys.create',
        'VPS.PublicKeys.attach',
        'VPS.PublicKeys.delete',

        // VPS Recovery
        'VPS.Recovery.start',
        'VPS.Recovery.stop',

        // VPS Snapshots
        'VPS.Snapshots.read',
        'VPS.Snapshots.create',
        'VPS.Snapshots.delete',
        'VPS.Snapshots.restore',

        // VPS Virtual Machine
        'VPS.VirtualMachine.PublicKeys.read',
        'VPS.VirtualMachine.Hostname.update',
        'VPS.VirtualMachine.Hostname.delete',
        'VPS.VirtualMachine.Manage.read',
        'VPS.VirtualMachine.Manage.details',
        'VPS.VirtualMachine.Manage.metrics',
        'VPS.VirtualMachine.Manage.nameservers',
        'VPS.VirtualMachine.Manage.recreate',
        'VPS.VirtualMachine.Manage.restart',
        'VPS.VirtualMachine.Manage.password',
        'VPS.VirtualMachine.Manage.start',
        'VPS.VirtualMachine.Manage.stop',
        'VPS.VirtualMachine.Purchase.create',
        'VPS.VirtualMachine.Purchase.setup',

        // Management
        'Manage.Invite.user',
        'Manage.Invite.list',
        'Manage.Invite.update',
        'Manage.Invite.delete',
        'Manage.Permissions.create',
        'Manage.Permissions.update',
        'Manage.Permissions.delete',
        'Manage.Permissions.list',
        'Manage.Permissions.read',
        'Manage.Permissions.VPS.attach',
        'Manage.Permissions.VPS.detach',
        'Manage.Permissions.VPS.all',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $root = Role::firstOrCreate(['name' => 'root', 'guard_name' => 'web']);
        $root->syncPermissions(Permission::all());
    }
}
