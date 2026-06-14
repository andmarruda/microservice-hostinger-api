import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/Dialog';
import { Label } from '@/components/ui/Label';
import { Select } from '@/components/ui/Select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    role: string;
}

interface VpsWithGrant {
    id: string;
    hostname: string;
    display_name?: string;
    status: string;
    ip_address?: string;
    grant_id: number;
    granted_at: string;
    expires_at: string | null;
    can_manage_firewall: boolean;
    can_manage_ssh_keys: boolean;
    can_manage_snapshots: boolean;
}

interface AvailableVps {
    id: string;
    hostname: string;
    display_name?: string;
    status: string;
    ip_address?: string;
}

interface Props {
    user: UserDetail;
    grantedVps: VpsWithGrant[];
    availableVps: AvailableVps[];
}

export default function UsersShow({ user, grantedVps, availableVps }: Props) {
    const [grantOpen, setGrantOpen] = useState(false);
    const [permOpen, setPermOpen] = useState(false);
    const [selectedVpsId, setSelectedVpsId] = useState<string | null>(null);

    const grantForm = useForm({
        vps_id: '',
        can_manage_firewall: false,
        can_manage_ssh_keys: false,
        can_manage_snapshots: false,
    });

    const permForm = useForm({
        can_manage_firewall: false,
        can_manage_ssh_keys: false,
        can_manage_snapshots: false,
    });

    const revokeForm = useForm({});
    const deleteForm = useForm({});

    function handleGrant(e: FormEvent) {
        e.preventDefault();
        grantForm.post(`/users/${user.id}/vps-access`, {
            onSuccess: () => {
                setGrantOpen(false);
                grantForm.reset();
            },
        });
    }

    function openPermDialog(vps: VpsWithGrant) {
        setSelectedVpsId(vps.id);
        permForm.setData({
            can_manage_firewall: vps.can_manage_firewall,
            can_manage_ssh_keys: vps.can_manage_ssh_keys,
            can_manage_snapshots: vps.can_manage_snapshots,
        });
        setPermOpen(true);
    }

    function handleUpdatePerms(e: FormEvent) {
        e.preventDefault();
        if (!selectedVpsId) return;
        permForm.put(`/users/${user.id}/vps-access/${selectedVpsId}/permissions`, {
            onSuccess: () => setPermOpen(false),
        });
    }

    function handleRevoke(vpsId: string) {
        revokeForm.delete(`/users/${user.id}/vps-access/${vpsId}`);
    }

    function handleDeleteUser() {
        if (!confirm(`Delete user ${user.name}? This cannot be undone.`)) return;
        deleteForm.delete(`/users/${user.id}`);
    }

    return (
        <AppLayout title={`User: ${user.name}`}>
            <Head title={`User: ${user.name}`} />

            <div className="mb-4 flex items-center gap-2">
                <Link href="/users" className="text-sm text-gray-500 hover:text-gray-700">
                    ← Users
                </Link>
            </div>

            {/* User info */}
            <div className="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div className="flex items-start justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">{user.name}</h2>
                        <p className="text-sm text-gray-500">{user.email}</p>
                        <div className="mt-2 flex items-center gap-2">
                            <Badge variant={user.role === 'admin' ? 'info' : 'default'}>
                                {user.role === 'admin' ? 'Admin' : 'User'}
                            </Badge>
                            <Badge variant={user.email_verified_at ? 'success' : 'warning'}>
                                {user.email_verified_at ? 'Active' : 'Unverified'}
                            </Badge>
                        </div>
                        <p className="mt-1 text-xs text-gray-400">
                            Created {new Date(user.created_at).toLocaleDateString()}
                        </p>
                    </div>
                    <Button variant="outline" onClick={handleDeleteUser} disabled={deleteForm.processing}>
                        Delete User
                    </Button>
                </div>
            </div>

            {/* VPS access */}
            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 className="font-medium text-gray-900">VPS Access</h3>
                    {availableVps.length > 0 && (
                        <Button onClick={() => setGrantOpen(true)}>Grant VPS Access</Button>
                    )}
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>VPS</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Firewall</TableHead>
                            <TableHead>SSH Keys</TableHead>
                            <TableHead>Snapshots</TableHead>
                            <TableHead>Granted</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {grantedVps.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} className="py-8 text-center text-gray-400">
                                    No VPS access granted.
                                </TableCell>
                            </TableRow>
                        )}
                        {grantedVps.map((vps) => (
                            <TableRow key={vps.id}>
                                <TableCell>
                                    <p className="font-medium text-gray-900">{vps.display_name ?? vps.hostname}</p>
                                    {vps.display_name && <p className="text-xs text-gray-400">{vps.hostname}</p>}
                                </TableCell>
                                <TableCell>
                                    <Badge variant={vps.status === 'running' ? 'success' : 'default'}>
                                        {vps.status}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={vps.can_manage_firewall ? 'success' : 'default'}>
                                        {vps.can_manage_firewall ? 'Yes' : 'No'}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={vps.can_manage_ssh_keys ? 'success' : 'default'}>
                                        {vps.can_manage_ssh_keys ? 'Yes' : 'No'}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={vps.can_manage_snapshots ? 'success' : 'default'}>
                                        {vps.can_manage_snapshots ? 'Yes' : 'No'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-xs text-gray-500">
                                    {new Date(vps.granted_at).toLocaleDateString()}
                                </TableCell>
                                <TableCell>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => openPermDialog(vps)}
                                            className="text-xs text-blue-600 hover:underline"
                                        >
                                            Permissions
                                        </button>
                                        <button
                                            onClick={() => handleRevoke(vps.id)}
                                            disabled={revokeForm.processing}
                                            className="text-xs text-red-600 hover:underline disabled:opacity-50"
                                        >
                                            Revoke
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Grant VPS Dialog */}
            <Dialog open={grantOpen} onClose={() => setGrantOpen(false)}>
                <DialogHeader>
                    <DialogTitle>Grant VPS Access</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleGrant}>
                    <DialogContent className="space-y-4">
                        {Object.keys(grantForm.errors).length > 0 && (
                            <Alert variant="destructive">
                                {Object.values(grantForm.errors)[0]}
                            </Alert>
                        )}
                        <div className="space-y-1.5">
                            <Label htmlFor="grant-vps">VPS</Label>
                            <Select
                                id="grant-vps"
                                value={grantForm.data.vps_id}
                                onChange={(e) => grantForm.setData('vps_id', e.target.value)}
                                required
                            >
                                <option value="">Select a VPS…</option>
                                {availableVps.map((vps) => (
                                    <option key={vps.id} value={vps.id}>
                                        {vps.display_name ?? vps.hostname} — {vps.ip_address ?? vps.hostname} ({vps.status})
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-gray-700">Permissions</p>
                            <CheckboxField
                                id="grant-firewall"
                                label="Manage Firewall"
                                checked={grantForm.data.can_manage_firewall}
                                onChange={(v) => grantForm.setData('can_manage_firewall', v)}
                            />
                            <CheckboxField
                                id="grant-ssh"
                                label="Manage SSH Keys"
                                checked={grantForm.data.can_manage_ssh_keys}
                                onChange={(v) => grantForm.setData('can_manage_ssh_keys', v)}
                            />
                            <CheckboxField
                                id="grant-snapshots"
                                label="Manage Snapshots"
                                checked={grantForm.data.can_manage_snapshots}
                                onChange={(v) => grantForm.setData('can_manage_snapshots', v)}
                            />
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setGrantOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={grantForm.processing}>
                            {grantForm.processing ? 'Granting…' : 'Grant Access'}
                        </Button>
                    </DialogFooter>
                </form>
            </Dialog>

            {/* Update Permissions Dialog */}
            <Dialog open={permOpen} onClose={() => setPermOpen(false)}>
                <DialogHeader>
                    <DialogTitle>Update Permissions</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleUpdatePerms}>
                    <DialogContent className="space-y-3">
                        <CheckboxField
                            id="perm-firewall"
                            label="Manage Firewall"
                            checked={permForm.data.can_manage_firewall}
                            onChange={(v) => permForm.setData('can_manage_firewall', v)}
                        />
                        <CheckboxField
                            id="perm-ssh"
                            label="Manage SSH Keys"
                            checked={permForm.data.can_manage_ssh_keys}
                            onChange={(v) => permForm.setData('can_manage_ssh_keys', v)}
                        />
                        <CheckboxField
                            id="perm-snapshots"
                            label="Manage Snapshots"
                            checked={permForm.data.can_manage_snapshots}
                            onChange={(v) => permForm.setData('can_manage_snapshots', v)}
                        />
                    </DialogContent>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setPermOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={permForm.processing}>
                            {permForm.processing ? 'Saving…' : 'Save'}
                        </Button>
                    </DialogFooter>
                </form>
            </Dialog>
        </AppLayout>
    );
}

function CheckboxField({
    id,
    label,
    checked,
    onChange,
}: {
    id: string;
    label: string;
    checked: boolean;
    onChange: (v: boolean) => void;
}) {
    return (
        <div className="flex items-center gap-2">
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
                className="h-4 w-4 rounded border-gray-300 accent-gray-900"
            />
            <Label htmlFor={id} className="cursor-pointer">
                {label}
            </Label>
        </div>
    );
}
