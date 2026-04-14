import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { PermissionApproval } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

interface Props {
    approvals: PermissionApproval[];
}

function statusVariant(status: string): 'warning' | 'success' | 'destructive' | 'default' {
    if (status === 'pending') return 'warning';
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'destructive';
    return 'default';
}

export default function ApprovalsIndex({ approvals }: Props) {
    const { auth } = usePage<SharedData>().props;

    function approve(id: string) {
        router.post(`/governance/approvals/${id}/approve`);
    }

    return (
        <AppLayout title="Approvals">
            <Head title="Approvals" />

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Requester</TableHead>
                            <TableHead>Permission</TableHead>
                            <TableHead>Reason</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Requested</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {approvals.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center text-gray-400 py-8">
                                    No pending approvals.
                                </TableCell>
                            </TableRow>
                        )}
                        {approvals.map((a) => (
                            <TableRow key={a.id}>
                                <TableCell className="font-medium text-gray-900">{a.requester_id}</TableCell>
                                <TableCell className="font-mono text-xs">{a.permission}</TableCell>
                                <TableCell className="text-gray-500 max-w-xs truncate">{a.reason ?? '—'}</TableCell>
                                <TableCell>
                                    <Badge variant={statusVariant(a.status)}>{a.status}</Badge>
                                </TableCell>
                                <TableCell className="text-gray-500">{a.created_at}</TableCell>
                                <TableCell>
                                    {a.status === 'pending' && String(a.requester_id) !== String(auth.user?.id) && (
                                        <Button
                                            size="sm"
                                            onClick={() => approve(a.id)}
                                        >
                                            Approve
                                        </Button>
                                    )}
                                    {a.status === 'pending' && String(a.requester_id) === String(auth.user?.id) && (
                                        <span className="text-xs text-gray-400">Awaiting review</span>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
