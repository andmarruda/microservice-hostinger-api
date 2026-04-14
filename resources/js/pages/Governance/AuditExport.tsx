import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Select } from '@/components/ui/Select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { InfraAuditLog } from '@/types';
import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    logs: InfraAuditLog[];
    filters: {
        actor_id?: string;
        action?: string;
        from?: string;
        to?: string;
    };
}

function outcomeVariant(outcome: string): 'success' | 'destructive' | 'default' {
    if (outcome === 'success') return 'success';
    if (outcome === 'failure') return 'destructive';
    return 'default';
}

export default function AuditExport({ logs, filters }: Props) {
    const [actorId, setActorId] = useState(filters.actor_id ?? '');
    const [action, setAction] = useState(filters.action ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    function handleFilter(e: FormEvent) {
        e.preventDefault();
        router.get('/governance/audit', { actor_id: actorId, action, from, to }, { preserveState: true });
    }

    function handleDownload() {
        const params = new URLSearchParams({ actor_id: actorId, action, from, to, format: 'csv' });
        window.location.href = `/governance/audit/export?${params}`;
    }

    return (
        <AppLayout title="Audit Export">
            <Head title="Audit Export" />

            <Card className="mb-6">
                <CardHeader><CardTitle>Filters</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={handleFilter} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label>Actor ID</Label>
                            <Input value={actorId} onChange={(e) => setActorId(e.target.value)} placeholder="User UUID" />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Action</Label>
                            <Input value={action} onChange={(e) => setAction(e.target.value)} placeholder="e.g. vps.start" />
                        </div>
                        <div className="space-y-1.5">
                            <Label>From</Label>
                            <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>To</Label>
                            <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                        </div>
                        <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                            <Button type="submit" variant="outline">Filter</Button>
                            <Button type="button" onClick={handleDownload}>Download CSV</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Timestamp</TableHead>
                            <TableHead>Actor</TableHead>
                            <TableHead>Action</TableHead>
                            <TableHead>Resource</TableHead>
                            <TableHead>Outcome</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {logs.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center text-gray-400 py-8">
                                    No audit logs found.
                                </TableCell>
                            </TableRow>
                        )}
                        {logs.map((log) => (
                            <TableRow key={log.id}>
                                <TableCell className="text-xs text-gray-500 whitespace-nowrap">{log.performed_at}</TableCell>
                                <TableCell className="text-gray-900">{log.actor_email}</TableCell>
                                <TableCell className="font-mono text-xs">{log.action}</TableCell>
                                <TableCell className="text-xs text-gray-500">{log.resource_type}{log.resource_id ? `:${log.resource_id}` : ''}</TableCell>
                                <TableCell>
                                    <Badge variant={outcomeVariant(log.outcome)}>{log.outcome}</Badge>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
