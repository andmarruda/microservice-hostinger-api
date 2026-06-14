import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/Dialog';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import AppLayout from '@/layouts/AppLayout';
import { SshKey, Vps } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Cpu, HardDrive, MemoryStick, Network, Pencil, Play, Plus, Power, RotateCcw, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Metric {
    cpu_usage: number;
    memory_usage: number;
    disk_usage: number;
    network_in: number;
    network_out: number;
}

interface Backup {
    id: string;
    created_at: string;
    size: number;
    state: string;
}

interface Props {
    vps: Vps | null;
    metrics: Metric | null;
    actions: unknown[];
    backups: Backup[];
    sshKeys: SshKey[];
}

function statusVariant(status: string): 'success' | 'warning' | 'destructive' | 'default' {
    if (status === 'running') return 'success';
    if (status === 'stopped') return 'destructive';
    if (status === 'starting' || status === 'stopping') return 'warning';
    return 'default';
}

function GaugeBar({ value }: { value: number }) {
    return (
        <div className="h-1.5 w-full rounded-full bg-gray-100">
            <div
                className={`h-1.5 rounded-full ${value >= 90 ? 'bg-red-500' : value >= 70 ? 'bg-yellow-500' : 'bg-green-500'}`}
                style={{ width: `${Math.min(value, 100)}%` }}
            />
        </div>
    );
}

function InfoCard({ vps }: { vps: Vps }) {
    const [renaming, setRenaming] = useState(false);
    const { data, setData, put, processing, errors, reset } = useForm({
        display_name: vps.display_name ?? vps.hostname,
    });
    const powerForm = useForm({});

    function handleRename(e: FormEvent) {
        e.preventDefault();
        put(`/vps/${vps.id}/name`, { onSuccess: () => setRenaming(false) });
    }

    return (
        <>
            <Card className="mb-6">
                <CardContent className="pt-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-semibold text-gray-900">
                                    {vps.display_name ?? vps.hostname}
                                </h1>
                                <button
                                    type="button"
                                    title="Rename"
                                    onClick={() => setRenaming(true)}
                                    className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                >
                                    <Pencil className="h-4 w-4" />
                                </button>
                            </div>
                            <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
                                <span><span className="font-medium text-gray-700">Hostname:</span> {vps.hostname}</span>
                                <span><span className="font-medium text-gray-700">IP:</span> <span className="font-mono">{vps.ip_address}</span></span>
                                <span><span className="font-medium text-gray-700">Plan:</span> {vps.plan}</span>
                                {vps.os && <span><span className="font-medium text-gray-700">OS:</span> {vps.os}</span>}
                                {vps.region && <span><span className="font-medium text-gray-700">Region:</span> {vps.region}</span>}
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Badge variant={statusVariant(vps.status)}>{vps.status}</Badge>
                            <div className="flex gap-1.5">
                                {vps.status === 'stopped' && (
                                    <Button
                                        size="sm"
                                        title="Start"
                                        disabled={powerForm.processing}
                                        onClick={() => powerForm.post(`/vps/${vps.id}/start`)}
                                    >
                                        <Play className="h-4 w-4" />
                                    </Button>
                                )}
                                {vps.status === 'running' && (
                                    <>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            title="Reboot"
                                            disabled={powerForm.processing}
                                            onClick={() => powerForm.post(`/vps/${vps.id}/reboot`)}
                                        >
                                            <RotateCcw className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            title="Stop"
                                            disabled={powerForm.processing}
                                            onClick={() => powerForm.post(`/vps/${vps.id}/stop`)}
                                        >
                                            <Power className="h-4 w-4" />
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Dialog open={renaming} onClose={() => { setRenaming(false); reset(); }}>
                <DialogHeader>
                    <DialogTitle>Rename VPS</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleRename}>
                    <DialogContent className="space-y-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="rename-display">Display name</Label>
                            <Input
                                id="rename-display"
                                value={data.display_name}
                                onChange={(e) => setData('display_name', e.target.value)}
                                autoFocus
                            />
                            {errors.display_name && <p className="text-xs text-red-600">{errors.display_name}</p>}
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => { setRenaming(false); reset(); }}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>Save</Button>
                    </DialogFooter>
                </form>
            </Dialog>
        </>
    );
}

function SshKeysCard({ vps, sshKeys }: { vps: Vps; sshKeys: SshKey[] }) {
    const [addOpen, setAddOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<SshKey | null>(null);

    const addForm = useForm({ key_name: '', public_key: '' });
    const removeForm = useForm({});

    function handleAdd(e: FormEvent) {
        e.preventDefault();
        addForm.post(`/vps/${vps.id}/ssh-keys`, {
            onSuccess: () => { setAddOpen(false); addForm.reset(); },
        });
    }

    function handleRemove() {
        if (!deleteTarget) return;
        removeForm.post(`/vps/${vps.id}/ssh-keys/${deleteTarget.id}/remove`, {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <>
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>SSH Keys</CardTitle>
                        <Button size="sm" variant="outline" onClick={() => setAddOpen(true)}>
                            <Plus className="mr-1 h-4 w-4" /> Add Key
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    {sshKeys.length === 0 ? (
                        <p className="text-sm text-gray-400">No SSH keys on this VPS.</p>
                    ) : (
                        <div className="space-y-2">
                            {sshKeys.map((key) => (
                                <div
                                    key={key.id}
                                    className="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-gray-900">{key.name}</p>
                                        <p className="truncate font-mono text-xs text-gray-500">{key.fingerprint}</p>
                                    </div>
                                    <button
                                        type="button"
                                        title="Remove key"
                                        onClick={() => setDeleteTarget(key)}
                                        className="shrink-0 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Add Key Modal */}
            <Dialog open={addOpen} onClose={() => { setAddOpen(false); addForm.reset(); }}>
                <DialogHeader>
                    <DialogTitle>Add SSH Key</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleAdd}>
                    <DialogContent className="space-y-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ssh-key-name">Key name</Label>
                            <Input
                                id="ssh-key-name"
                                value={addForm.data.key_name}
                                onChange={(e) => addForm.setData('key_name', e.target.value)}
                                placeholder="anderson-laptop"
                                autoFocus
                            />
                            {addForm.errors.key_name && <p className="text-xs text-red-600">{addForm.errors.key_name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ssh-pub-key">Public key</Label>
                            <textarea
                                id="ssh-pub-key"
                                value={addForm.data.public_key}
                                onChange={(e) => addForm.setData('public_key', e.target.value)}
                                className="min-h-28 w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                placeholder="ssh-ed25519 AAAA..."
                            />
                            {addForm.errors.public_key && <p className="text-xs text-red-600">{addForm.errors.public_key}</p>}
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => { setAddOpen(false); addForm.reset(); }}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={addForm.processing}>Add Key</Button>
                    </DialogFooter>
                </form>
            </Dialog>

            {/* Delete Confirm Modal */}
            <Dialog open={!!deleteTarget} onClose={() => setDeleteTarget(null)}>
                <DialogHeader>
                    <DialogTitle>Remove SSH Key</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    <p className="text-sm text-gray-600">
                        Remove key <span className="font-medium text-gray-900">{deleteTarget?.name}</span> from this VPS? This cannot be undone.
                    </p>
                </DialogContent>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => setDeleteTarget(null)}>
                        Cancel
                    </Button>
                    <Button type="button" variant="destructive" disabled={removeForm.processing} onClick={handleRemove}>
                        Remove
                    </Button>
                </DialogFooter>
            </Dialog>
        </>
    );
}

function PasswordCard({ vps }: { vps: Vps }) {
    const { data, setData, put, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        put(`/vps/${vps.id}/password`, { onSuccess: () => reset() });
    }

    return (
        <Card>
            <CardHeader><CardTitle>Change Password</CardTitle></CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-3">
                    <div className="space-y-1.5">
                        <Label htmlFor="vps-password">New password</Label>
                        <Input
                            id="vps-password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        {errors.password && <p className="text-xs text-red-600">{errors.password}</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="vps-password-confirmation">Confirm password</Label>
                        <Input
                            id="vps-password-confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>
                    <Button type="submit" disabled={processing}>Change Password</Button>
                </form>
            </CardContent>
        </Card>
    );
}

function MetricStatCard({ label, icon: Icon, value, suffix }: { label: string; icon: (props: { className?: string }) => JSX.Element; value: number; suffix: string }) {
    const display = typeof value === 'number' && isFinite(value)
        ? (value % 1 === 0 ? String(value) : value.toFixed(1))
        : '—';
    const gauge = suffix === '%' && typeof value === 'number' ? value : 0;

    return (
        <Card>
            <CardContent className="pt-4">
                <div className="flex items-center gap-2 text-gray-500 mb-2">
                    <Icon className="h-4 w-4" />
                    <span className="text-xs font-medium uppercase tracking-wide">{label}</span>
                </div>
                <p className="text-2xl font-semibold text-gray-900">{display}{suffix}</p>
                <GaugeBar value={gauge} />
            </CardContent>
        </Card>
    );
}

export default function VpsShow({ vps, metrics, sshKeys }: Props) {
    if (!vps) {
        return (
            <AppLayout title="VPS">
                <Head title="VPS" />
                <p className="text-sm text-gray-500">VPS details unavailable. The server may be unreachable.</p>
            </AppLayout>
        );
    }

    return (
        <AppLayout title={vps.display_name ?? vps.hostname}>
            <Head title={vps.display_name ?? vps.hostname} />

            <InfoCard vps={vps} />

            <div className="grid gap-4 lg:grid-cols-2">
                <SshKeysCard vps={vps} sshKeys={sshKeys} />
                <PasswordCard vps={vps} />
            </div>

            {metrics && (
                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <MetricStatCard label="CPU" icon={Cpu} value={metrics.cpu_usage} suffix="%" />
                    <MetricStatCard label="Memory" icon={MemoryStick} value={metrics.memory_usage} suffix="%" />
                    <MetricStatCard label="Disk" icon={HardDrive} value={metrics.disk_usage} suffix="%" />
                    <MetricStatCard label="Net In" icon={Network} value={metrics.network_in / 1024 / 1024} suffix=" MB" />
                </div>
            )}
        </AppLayout>
    );
}
