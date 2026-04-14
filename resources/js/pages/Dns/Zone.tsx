import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';

interface DnsRecord {
    id: string;
    type: string;
    name: string;
    content: string;
    ttl: number;
    priority?: number;
}

interface Props {
    domain: string;
    records: DnsRecord[];
}

const recordTypeColors: Record<string, 'default' | 'info' | 'success' | 'warning'> = {
    A: 'success',
    AAAA: 'success',
    CNAME: 'info',
    MX: 'warning',
    TXT: 'default',
    NS: 'default',
    SOA: 'default',
};

export default function DnsZone({ domain, records }: Props) {
    const grouped = records.reduce<Record<string, DnsRecord[]>>((acc, r) => {
        acc[r.type] = acc[r.type] ?? [];
        acc[r.type].push(r);
        return acc;
    }, {});

    return (
        <AppLayout title={`DNS — ${domain}`}>
            <Head title={`DNS Zone — ${domain}`} />

            <div className="mb-4">
                <h2 className="text-sm text-gray-500">Zone: <span className="font-medium text-gray-900">{domain}</span></h2>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        DNS Records
                        <span className="text-sm font-normal text-gray-400">({records.length} total)</span>
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Type</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Content</TableHead>
                                <TableHead className="text-right">TTL</TableHead>
                                <TableHead className="text-right">Priority</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {records.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-gray-400 py-8">
                                        No DNS records found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {records.map((r) => (
                                <TableRow key={r.id}>
                                    <TableCell>
                                        <Badge variant={recordTypeColors[r.type] ?? 'default'}>{r.type}</Badge>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs text-gray-900">{r.name}</TableCell>
                                    <TableCell className="font-mono text-xs text-gray-500 max-w-xs truncate">{r.content}</TableCell>
                                    <TableCell className="text-right text-xs text-gray-400">{r.ttl}s</TableCell>
                                    <TableCell className="text-right text-xs text-gray-400">{r.priority ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
