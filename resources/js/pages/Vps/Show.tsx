import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import AppLayout from '@/layouts/AppLayout';
import { Vps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

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
}

type Tab = 'details' | 'metrics' | 'actions' | 'backups';

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

export default function VpsShow({ vps, metrics, actions, backups }: Props) {
    const [tab, setTab] = useState<Tab>('details');

    const tabs: { key: Tab; label: string }[] = [
        { key: 'details', label: 'Details' },
        { key: 'metrics', label: 'Metrics' },
        { key: 'actions', label: 'Actions' },
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
                        {[
                            ['Hostname', vps.hostname],
                            ['Plan', vps.plan],
                            ['IP Address', vps.ip_address],
                            ['Region', vps.region ?? '—'],
                            ['OS', vps.os ?? '—'],
                            ['CPUs', vps.cpus ?? '—'],
                            ['RAM (MB)', vps.ram ?? '—'],
                            ['Disk (GB)', vps.disk ?? '—'],
                        ].map(([label, val]) => (
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

            {tab === 'actions' && (
                <Card>
                    <CardHeader><CardTitle>Recent Actions</CardTitle></CardHeader>
                    <CardContent>
                        {actions.length === 0 ? (
                            <p className="text-sm text-gray-400">No actions recorded.</p>
                        ) : (
                            <div className="space-y-2">
                                {actions.map((a) => (
                                    <div key={a.id} className="flex items-center justify-between text-sm">
                                        <span className="font-medium text-gray-900">{a.type}</span>
                                        <Badge variant={a.state === 'success' ? 'success' : a.state === 'error' ? 'destructive' : 'default'}>
                                            {a.state}
                                        </Badge>
                                        <span className="text-gray-400">{a.started_at}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

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
