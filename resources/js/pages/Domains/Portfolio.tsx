import { Badge } from '@/components/ui/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

interface Domain {
    domain: string;
    status: string;
    expires_at: string | null;
    auto_renew: boolean;
}

interface Props {
    domains: Domain[];
}

function statusVariant(status: string): 'success' | 'warning' | 'destructive' | 'default' {
    if (status === 'active') return 'success';
    if (status === 'expiring_soon') return 'warning';
    if (status === 'expired') return 'destructive';
    return 'default';
}

export default function DomainsPortfolio({ domains }: Props) {
    return (
        <AppLayout title="Domains">
            <Head title="Domains" />

            <div className="mb-4 flex justify-end">
                <Link
                    href="/domains/check"
                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Check availability
                </Link>
            </div>

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Domain</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Expires</TableHead>
                            <TableHead>Auto-renew</TableHead>
                            <TableHead>DNS</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {domains.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center text-gray-400 py-8">
                                    No domains found.
                                </TableCell>
                            </TableRow>
                        )}
                        {domains.map((d) => (
                            <TableRow key={d.domain}>
                                <TableCell className="font-medium text-gray-900">{d.domain}</TableCell>
                                <TableCell>
                                    <Badge variant={statusVariant(d.status)}>{d.status}</Badge>
                                </TableCell>
                                <TableCell className="text-gray-500">{d.expires_at ?? '—'}</TableCell>
                                <TableCell>
                                    <Badge variant={d.auto_renew ? 'success' : 'default'}>
                                        {d.auto_renew ? 'on' : 'off'}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Link
                                        href={`/dns/${d.domain}`}
                                        className="text-sm text-gray-500 hover:text-gray-900 hover:underline"
                                    >
                                        DNS Zone
                                    </Link>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
