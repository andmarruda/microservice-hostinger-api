import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import AppLayout from '@/layouts/AppLayout';
import { SshKey, Vps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Metric {
    cpu_usage: number;
    memory_usage: number;
    disk_usage: number;
    network_in: number;
    network_out: number;
}

interface Action {
    id: string;
    type: string;
    state: string;
    started_at: string;
    completed_at: string | null;
}

interface Backup {
    id: string;
    created_at: string;
    size: number;
    state: string;
}

interface Props {
    vps: Vps;
    metrics: Metric | null;
    actions: Action[];
    backups: Backup[];
    sshKeys: SshKey[];
}

type Tab = 'details' | 'metrics' | 'edit' | 'backups';

function statusVariant(status: string): 'success' | 'warning' | 'destructive' | 'default' {
    if (status === 'running') return 'success';
    if (status === 'stopped') return 'destructive';
    if (status === 'starting' || status === 'stopping') return 'warning';
    return 'default';
}

function GaugeBar({ label, value }: { label: string; value: number }) {
    return (
        <div>
            <div className="flex justify-between text-sm mb-1">
                <span className="text-gray-600">{label}</span>
                <span className="font-medium text-gray-900">{value.toFixed(1)}%</span>
            </div>
            <div className="h-2 w-full rounded-full bg-gray-100">
                <div
                    className={`h-2 rounded-full ${value >= 90 ? 'bg-red-500' : value >= 70 ? 'bg-yellow-500' : 'bg-green-500'}`}
                    style={{ width: `${Math.min(value, 100)}%` }}
                />
            </div>
        </div>
    );
}

export default function VpsShow({ vps, metrics, backups, sshKeys }: Props) {
    const [tab, setTab] = useState<Tab>('details');
    const detailRows: [string, string][] = [
        ['Name', String(vps.display_name ?? vps.hostname)],
        ['Hostname', vps.hostname],
        ['Plan', vps.plan],
        ['IP Address', vps.ip_address],
        ['Region', String(vps.region ?? '—')],
        ['OS', String(vps.os ?? '—')],
        ['CPUs', String(vps.cpus ?? '—')],
        ['RAM (MB)', String(vps.ram ?? '—')],
        ['Disk (GB)', String(vps.disk ?? '—')],
    ];

    const tabs: { key: Tab; label: string }[] = [
        { key: 'details', label: 'Details' },
        { key: 'metrics', label: 'Metrics' },
        { key: 'edit', label: 'Edit' },
        { key: 'backups', label: 'Backups' },
    ];

    return (
        <AppLayout title={vps.hostname}>
            <Head title={vps.hostname} />

            {/* Sub-nav links */}
            <div className="mb-4 flex gap-2 text-sm">
                <Link href={`/vps/${vps.id}/firewall`} className="text-gray-500 hover:text-gray-900">Firewall</Link>
                <span className="text-gray-300">·</span>
                <Link href={`/vps/${vps.id}/ssh-keys`} className="text-gray-500 hover:text-gray-900">SSH Keys</Link>
                <span className="text-gray-300">·</span>
                <Link href={`/vps/${vps.id}/snapshots`} className="text-gray-500 hover:text-gray-900">Snapshots</Link>
            </div>

            {/* Tabs */}
            <div className="mb-6 flex gap-1 border-b border-gray-200">
                {tabs.map(({ key, label }) => (
                    <button
                        key={key}
                        onClick={() => setTab(key)}
                        className={`px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px ${
                            tab === key
                                ? 'border-gray-900 text-gray-900'
                                : 'border-transparent text-gray-500 hover:text-gray-900'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'details' && (
                <Card>
                    <CardHeader><CardTitle>VPS Details</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {detailRows.map(([label, val]) => (
                            <div key={String(label)} className="flex items-center justify-between text-sm">
                                <span className="text-gray-500">{label}</span>
                                <span className="font-medium text-gray-900">{val}</span>
                            </div>
                        ))}
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-gray-500">Status</span>
                            <Badge variant={statusVariant(vps.status)}>{vps.status}</Badge>
                        </div>
                    </CardContent>
                </Card>
            )}

            {tab === 'metrics' && (
                <Card>
                    <CardHeader><CardTitle>Resource Usage</CardTitle></CardHeader>
                    <CardContent>
                        {metrics ? (
                            <div className="space-y-4">
                                <GaugeBar label="CPU" value={metrics.cpu_usage} />
                                <GaugeBar label="Memory" value={metrics.memory_usage} />
                                <GaugeBar label="Disk" value={metrics.disk_usage} />
                                <div className="flex gap-6 text-sm pt-2">
                                    <div>
                                        <span className="text-gray-500">Network in: </span>
                                        <span className="font-medium">{(metrics.network_in / 1024 / 1024).toFixed(1)} MB</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500">Network out: </span>
                                        <span className="font-medium">{(metrics.network_out / 1024 / 1024).toFixed(1)} MB</span>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400">No metrics available.</p>
                        )}
                    </CardContent>
                </Card>
            )}

            {tab === 'edit' && <VpsEditPanel vps={vps} sshKeys={sshKeys} metrics={metrics} />}

            {tab === 'backups' && (
                <Card>
                    <CardHeader><CardTitle>Backups</CardTitle></CardHeader>
                    <CardContent>
                        {backups.length === 0 ? (
                            <p className="text-sm text-gray-400">No backups available.</p>
                        ) : (
                            <div className="space-y-2">
                                {backups.map((b) => (
                                    <div key={b.id} className="flex items-center justify-between text-sm">
                                        <span className="text-gray-900">{b.created_at}</span>
                                        <span className="text-gray-500">{(b.size / 1024 / 1024).toFixed(0)} MB</span>
                                        <Badge variant={b.state === 'completed' ? 'success' : 'default'}>{b.state}</Badge>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}
        </AppLayout>
    );
}

function VpsEditPanel({ vps, sshKeys, metrics }: { vps: Vps; sshKeys: SshKey[]; metrics: Metric | null }) {
    const sshForm = useForm({
        key_name: '',
        public_key: '',
    });
    const passwordForm = useForm({
        password: '',
        password_confirmation: '',
    });
    const removeForm = useForm({});

    function handleAddSshKey(e: FormEvent) {
        e.preventDefault();
        sshForm.post(`/vps/${vps.id}/ssh-keys`, {
            onSuccess: () => sshForm.reset(),
        });
    }

    function handlePasswordChange(e: FormEvent) {
        e.preventDefault();
        passwordForm.put(`/vps/${vps.id}/password`, {
            onSuccess: () => passwordForm.reset(),
        });
    }

    function handleRemoveSshKey(keyId: string | number) {
        removeForm.post(`/vps/${vps.id}/ssh-keys/${keyId}/remove`);
    }

    return (
        <div className="grid gap-4 lg:grid-cols-[1fr_1fr]">
            <Card>
                <CardHeader><CardTitle>Access</CardTitle></CardHeader>
                <CardContent className="space-y-5">
                    <form onSubmit={handleAddSshKey} className="space-y-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ssh-key-name">SSH key name</Label>
                            <Input
                                id="ssh-key-name"
                                value={sshForm.data.key_name}
                                onChange={(e) => sshForm.setData('key_name', e.target.value)}
                                placeholder="anderson-laptop"
                            />
                            {sshForm.errors.key_name && <p className="text-xs text-red-600">{sshForm.errors.key_name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ssh-public-key">Public key</Label>
                            <textarea
                                id="ssh-public-key"
                                value={sshForm.data.public_key}
                                onChange={(e) => sshForm.setData('public_key', e.target.value)}
                                className="min-h-24 w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                placeholder="ssh-ed25519 AAAA..."
                            />
                            {sshForm.errors.public_key && <p className="text-xs text-red-600">{sshForm.errors.public_key}</p>}
                        </div>
                        <Button type="submit" disabled={sshForm.processing}>Add SSH Key</Button>
                    </form>

                    <div className="space-y-2 border-t border-gray-100 pt-4">
                        {sshKeys.length === 0 ? (
                            <p className="text-sm text-gray-400">No SSH keys returned for this VPS.</p>
                        ) : (
                            sshKeys.map((key) => (
                                <div key={key.id} className="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-gray-900">{key.name}</p>
                                        <p className="truncate font-mono text-xs text-gray-500">{key.fingerprint}</p>
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        disabled={removeForm.processing}
                                        onClick={() => handleRemoveSshKey(key.id)}
                                    >
                                        Remove
                                    </Button>
                                </div>
                            ))
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Dashboard</CardTitle></CardHeader>
                <CardContent className="space-y-5">
                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <DashboardStat label="Status" value={vps.status} />
                        <DashboardStat label="IP" value={vps.ip_address} />
                        <DashboardStat label="Region" value={String(vps.region ?? '—')} />
                        <DashboardStat label="OS" value={String(vps.os ?? '—')} />
                        <DashboardStat label="CPU" value={vps.cpus ? `${vps.cpus} cores` : '—'} />
                        <DashboardStat label="RAM" value={vps.ram ? `${vps.ram} MB` : '—'} />
                    </div>

                    {metrics && (
                        <div className="space-y-3 border-t border-gray-100 pt-4">
                            <GaugeBar label="CPU" value={metrics.cpu_usage} />
                            <GaugeBar label="Memory" value={metrics.memory_usage} />
                            <GaugeBar label="Disk" value={metrics.disk_usage} />
                        </div>
                    )}

                    <form onSubmit={handlePasswordChange} className="space-y-3 border-t border-gray-100 pt-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="vps-password">New password</Label>
                            <Input
                                id="vps-password"
                                type="password"
                                autoComplete="new-password"
                                value={passwordForm.data.password}
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                            />
                            {passwordForm.errors.password && <p className="text-xs text-red-600">{passwordForm.errors.password}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="vps-password-confirmation">Confirm password</Label>
                            <Input
                                id="vps-password-confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={passwordForm.data.password_confirmation}
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                            />
                        </div>
                        <Button type="submit" disabled={passwordForm.processing}>Change Password</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

function DashboardStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-md border border-gray-200 p-3">
            <p className="text-xs text-gray-500">{label}</p>
            <p className="mt-1 truncate font-medium text-gray-900">{value}</p>
        </div>
    );
}
