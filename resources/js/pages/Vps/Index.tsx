import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Vps } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Play, Power, RotateCcw } from 'lucide-react';
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
        <span className="font-medium text-gray-900">{vps.display_name ?? vps.hostname}</span>
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
            <button
                type="button"
                title="Rename"
                onClick={(e) => { e.stopPropagation(); onRename(); }}
                className="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
            >
                <Pencil className="h-4 w-4" />
            </button>

            {status === 'stopped' && (
                <button
                    type="button"
                    title="Start"
                    disabled={processing}
                    onClick={(e) => { e.stopPropagation(); post(`/vps/${id}/start`); }}
                    className="rounded p-1.5 text-green-600 hover:bg-green-50 hover:text-green-800 disabled:opacity-50"
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
                        className="rounded p-1.5 text-yellow-600 hover:bg-yellow-50 hover:text-yellow-800 disabled:opacity-50"
                    >
                        <RotateCcw className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        title="Stop"
                        disabled={processing}
                        onClick={(e) => { e.stopPropagation(); post(`/vps/${id}/stop`); }}
                        className="rounded p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 disabled:opacity-50"
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

    return (
        <TableRow
            key={v.id}
            className="cursor-pointer hover:bg-gray-50"
            onClick={() => !renaming && router.visit(`/vps/${v.id}`)}
        >
            <TableCell onClick={(e) => renaming && e.stopPropagation()}>
                <EditableVpsName vps={v} editing={renaming} onEditingChange={setRenaming} />
            </TableCell>
            <TableCell className="text-gray-500">{v.hostname}</TableCell>
            <TableCell className="text-gray-500">{v.plan}</TableCell>
            <TableCell className="font-mono text-xs text-gray-500">{v.ip_address}</TableCell>
            <TableCell>
                <Badge variant={statusVariant(v.status)}>{v.status}</Badge>
            </TableCell>
            <TableCell onClick={(e) => e.stopPropagation()}>
                <VpsActions id={v.id} status={v.status} onRename={() => setRenaming(true)} />
            </TableCell>
        </TableRow>
    );
}

export default function VpsIndex({ vps }: Props) {
    return (
        <AppLayout title="VPS">
            <Head title="VPS" />

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Hostname</TableHead>
                            <TableHead>Plan</TableHead>
                            <TableHead>IP</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {vps.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="py-8 text-center text-gray-400">
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
        </AppLayout>
    );
}
