import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Snapshot, Vps } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    vps: Vps;
    snapshots: Snapshot[];
}

export default function VpsSnapshots({ vps, snapshots }: Props) {
    return (
        <AppLayout title={`${vps.hostname} — Snapshots`}>
            <Head title={`Snapshots — ${vps.hostname}`} />

            <Card>
                <CardHeader>
                    <CardTitle>Snapshots</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Size</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {snapshots.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-center text-gray-400 py-8">
                                        No snapshots found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {snapshots.map((s) => (
                                <TableRow key={s.id}>
                                    <TableCell className="font-medium text-gray-900">{s.name}</TableCell>
                                    <TableCell className="text-gray-500">
                                        {s.size ? `${(s.size / 1024 / 1024).toFixed(0)} MB` : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={s.status === 'completed' ? 'success' : s.status === 'failed' ? 'destructive' : 'warning'}>
                                            {s.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-gray-500">{s.created_at}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
