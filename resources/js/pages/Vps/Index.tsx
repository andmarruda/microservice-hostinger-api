import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Vps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Cpu, Eye, HardDrive, MapPin, MemoryStick, Pencil, Play, Power, RotateCcw, Server } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Props {
    vps: Vps[];
}

function statusVariant(status: string): 'success' | 'warning' | 'destructive' | 'default' {
    if (status === 'running') return 'success';
    if (status === 'stopped') return 'destructive';
    if (status === 'starting' || status === 'stopping') return 'warning';
    return 'default';
}

function formatSpec(value: unknown, suffix = '') {
    if (value === null || value === undefined || value === '') return '—';
    return `${value}${suffix}`;
}

function serverMeta(vps: Vps) {
    return [
        { label: 'CPU', value: formatSpec(vps.cpus, ' vCPU'), icon: Cpu },
        { label: 'RAM', value: formatSpec(vps.ram, ' MB'), icon: MemoryStick },
        { label: 'Disk', value: formatSpec(vps.disk, ' GB'), icon: HardDrive },
        { label: 'Region', value: formatSpec(vps.region), icon: MapPin },
    ];
}

interface EditableVpsNameProps {
    vps: Vps;
    editing: boolean;
    onEditingChange: (v: boolean) => void;
}

function EditableVpsName({ vps, editing, onEditingChange }: EditableVpsNameProps) {
    const { data, setData, put, processing, errors, reset } = useForm({
        display_name: vps.display_name ?? vps.hostname,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        put(`/vps/${vps.id}/name`, { onSuccess: () => onEditingChange(false) });
    }

    function handleCancel() {
        reset();
        onEditingChange(false);
    }

    if (editing) {
        return (
            <form
                onSubmit={handleSubmit}
                onClick={(e) => e.stopPropagation()}
                className="flex min-w-56 items-center gap-2"
            >
                <Input
                    aria-label={`Name for ${vps.hostname}`}
                    value={data.display_name}
                    onChange={(e) => setData('display_name', e.target.value)}
                    autoFocus
                    className="h-8"
                />
                <Button size="sm" type="submit" disabled={processing}>
                    Save
                </Button>
                <Button size="sm" type="button" variant="outline" onClick={handleCancel}>
                    Cancel
                </Button>
                {errors.display_name && <span className="text-xs text-red-600">{errors.display_name}</span>}
            </form>
        );
    }

    return (
        <Link
            href={`/vps/${vps.id}`}
            onClick={(e) => e.stopPropagation()}
            className="font-semibold text-gray-950 hover:text-blue-700"
        >
            {vps.display_name ?? vps.hostname}
        </Link>
    );
}

interface VpsActionsProps {
    id: string;
    status: string;
    onRename: () => void;
}

function VpsActions({ id, status, onRename }: VpsActionsProps) {
    const { post, processing } = useForm({});

    return (
        <div className="flex items-center gap-1">
            <Link
                href={`/vps/${id}`}
                title="View dashboard"
                aria-label="View dashboard"
                onClick={(e) => e.stopPropagation()}
                className="rounded-md p-1.5 text-blue-600 transition hover:bg-blue-50 hover:text-blue-800"
            >
                <Eye className="h-4 w-4" />
            </Link>

            <button
                type="button"
                title="Rename"
                onClick={(e) => { e.stopPropagation(); onRename(); }}
                className="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
            >
                <Pencil className="h-4 w-4" />
            </button>

            {status === 'stopped' && (
                <button
                    type="button"
                    title="Start"
                    disabled={processing}
                    onClick={(e) => { e.stopPropagation(); post(`/vps/${id}/start`); }}
                    className="rounded-md p-1.5 text-green-600 transition hover:bg-green-50 hover:text-green-800 disabled:opacity-50"
                >
                    <Play className="h-4 w-4" />
                </button>
            )}

            {status === 'running' && (
                <>
                    <button
                        type="button"
                        title="Reboot"
                        disabled={processing}
                        onClick={(e) => { e.stopPropagation(); post(`/vps/${id}/reboot`); }}
                        className="rounded-md p-1.5 text-yellow-600 transition hover:bg-yellow-50 hover:text-yellow-800 disabled:opacity-50"
                    >
                        <RotateCcw className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        title="Stop"
                        disabled={processing}
                        onClick={(e) => { e.stopPropagation(); post(`/vps/${id}/stop`); }}
                        className="rounded-md p-1.5 text-red-500 transition hover:bg-red-50 hover:text-red-700 disabled:opacity-50"
                    >
                        <Power className="h-4 w-4" />
                    </button>
                </>
            )}
        </div>
    );
}

function VpsRow({ v }: { v: Vps }) {
    const [renaming, setRenaming] = useState(false);
    const meta = serverMeta(v).filter((item) => item.value !== '—').slice(0, 3);

    return (
        <TableRow
            key={v.id}
            className="cursor-pointer border-b border-gray-100 bg-white/80 hover:bg-blue-50/60"
            onClick={() => !renaming && router.visit(`/vps/${v.id}`)}
        >
            <TableCell className="min-w-64 py-4" onClick={(e) => renaming && e.stopPropagation()}>
                <div className="flex items-start gap-3">
                    <div className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-950 text-white shadow-sm">
                        <Server className="h-5 w-5" />
                    </div>
                    <div className="min-w-0 space-y-1">
                        <EditableVpsName vps={v} editing={renaming} onEditingChange={setRenaming} />
                        <p className="truncate text-xs text-gray-500">{v.hostname}</p>
                    </div>
                </div>
            </TableCell>
            <TableCell className="text-gray-600">
                <div className="space-y-1">
                    <p className="font-medium text-gray-900">{formatSpec(v.plan)}</p>
                    {v.os && <p className="max-w-52 truncate text-xs text-gray-500">{v.os}</p>}
                </div>
            </TableCell>
            <TableCell className="font-mono text-xs text-gray-600">{formatSpec(v.ip_address)}</TableCell>
            <TableCell className="min-w-56">
                <div className="flex flex-wrap gap-2">
                    {meta.length === 0 ? (
                        <span className="text-xs text-gray-400">No specs available</span>
                    ) : (
                        meta.map(({ label, value, icon: Icon }) => (
                            <span
                                key={label}
                                className="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-600"
                            >
                                <Icon className="h-3.5 w-3.5 text-gray-400" />
                                {value}
                            </span>
                        ))
                    )}
                </div>
            </TableCell>
            <TableCell>
                <Badge variant={statusVariant(v.status)}>{v.status}</Badge>
            </TableCell>
            <TableCell className="w-40" onClick={(e) => e.stopPropagation()}>
                <VpsActions id={v.id} status={v.status} onRename={() => setRenaming(true)} />
            </TableCell>
        </TableRow>
    );
}

export default function VpsIndex({ vps }: Props) {
    const runningCount = vps.filter((item) => item.status === 'running').length;
    const stoppedCount = vps.filter((item) => item.status === 'stopped').length;
    const regions = new Set(vps.map((item) => item.region).filter(Boolean)).size;

    return (
        <AppLayout title="VPS">
            <Head title="VPS" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-lg border border-gray-200 bg-gray-950 text-white shadow-sm">
                    <div className="grid gap-6 p-6 lg:grid-cols-[1.5fr_1fr] lg:items-end">
                        <div className="space-y-3">
                            <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-medium text-blue-100">
                                <Server className="h-3.5 w-3.5" />
                                Infrastructure overview
                            </div>
                            <div>
                                <h2 className="text-2xl font-semibold">VPS fleet</h2>
                                <p className="mt-1 max-w-2xl text-sm text-gray-300">
                                    Servers, lifecycle actions, network identity, and hardware shape in one operational view.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            {[
                                ['Total', vps.length],
                                ['Running', runningCount],
                                ['Regions', regions || '—'],
                            ].map(([label, value]) => (
                                <div key={label} className="rounded-lg border border-white/10 bg-white/10 p-3">
                                    <p className="text-xs text-gray-300">{label}</p>
                                    <p className="mt-1 text-xl font-semibold">{value}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-xs font-medium uppercase text-gray-500">Running now</p>
                        <p className="mt-2 text-2xl font-semibold text-gray-950">{runningCount}</p>
                    </div>
                    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-xs font-medium uppercase text-gray-500">Stopped</p>
                        <p className="mt-2 text-2xl font-semibold text-gray-950">{stoppedCount}</p>
                    </div>
                    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-xs font-medium uppercase text-gray-500">Managed IPs</p>
                        <p className="mt-2 text-2xl font-semibold text-gray-950">
                            {vps.filter((item) => item.ip_address).length}
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <Table>
                        <TableHeader className="bg-gray-50">
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Plan / OS</TableHead>
                                <TableHead>IP</TableHead>
                                <TableHead>Server</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vps.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-12 text-center text-gray-400">
                                        No VPS instances found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {vps.map((v) => (
                                <VpsRow key={v.id} v={v} />
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
