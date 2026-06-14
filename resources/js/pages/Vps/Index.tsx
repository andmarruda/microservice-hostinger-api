import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Vps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
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

function VpsActions({ id, status }: { id: string; status: string }) {
    const { post, processing } = useForm({});

    return (
        <div className="flex gap-1.5">
            {status === 'stopped' && (
                <Button
                    size="sm"
                    variant="outline"
                    disabled={processing}
                    onClick={() => post(`/vps/${id}/start`)}
                >
                    Start
                </Button>
            )}
            {status === 'running' && (
                <>
                    <Button
                        size="sm"
                        variant="outline"
                        disabled={processing}
                        onClick={() => post(`/vps/${id}/reboot`)}
                    >
                        Reboot
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        disabled={processing}
                        onClick={() => post(`/vps/${id}/stop`)}
                    >
                        Stop
                    </Button>
                </>
            )}
        </div>
    );
}

function EditableVpsName({ vps }: { vps: Vps }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, put, processing, errors } = useForm({
        display_name: String(vps.display_name ?? vps.hostname),
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        put(`/vps/${vps.id}/name`, {
            onSuccess: () => setEditing(false),
        });
    }

    if (editing) {
        return (
            <form onSubmit={handleSubmit} className="flex min-w-56 items-center gap-2">
                <Input
                    aria-label={`Name for ${vps.hostname}`}
                    value={data.display_name}
                    onChange={(e) => setData('display_name', e.target.value)}
                    autoFocus
                    className="h-8"
                />
                <Button size="sm" type="submit" disabled={processing}>
                    Confirm
                </Button>
                <Button size="sm" type="button" variant="outline" onClick={() => setEditing(false)}>
                    Cancel
                </Button>
                {errors.display_name && <span className="text-xs text-red-600">{errors.display_name}</span>}
            </form>
        );
    }

    return (
        <button
            type="button"
            onDoubleClick={() => setEditing(true)}
            className="text-left font-medium text-gray-900 hover:underline"
            title="Double click to edit"
        >
            {String(vps.display_name ?? vps.hostname)}
        </button>
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
                                <TableCell colSpan={6} className="text-center text-gray-400 py-8">
                                    No VPS instances found.
                                </TableCell>
                            </TableRow>
                        )}
                        {vps.map((v) => (
                            <TableRow key={v.id}>
                                <TableCell>
                                    <EditableVpsName vps={v} />
                                </TableCell>
                                <TableCell>
                                    <Link href={`/vps/${v.id}`} className="text-gray-500 hover:underline">
                                        {v.hostname}
                                    </Link>
                                </TableCell>
                                <TableCell className="text-gray-500">{v.plan}</TableCell>
                                <TableCell className="font-mono text-xs text-gray-500">{v.ip_address}</TableCell>
                                <TableCell>
                                    <Badge variant={statusVariant(v.status)}>{v.status}</Badge>
                                </TableCell>
                                <TableCell>
                                    <VpsActions id={v.id} status={v.status} />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
