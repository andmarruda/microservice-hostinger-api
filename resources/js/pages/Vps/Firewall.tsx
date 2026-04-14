import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { FirewallRule, Vps } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    vps: Vps;
    rules: FirewallRule[];
}

export default function VpsFirewall({ vps, rules }: Props) {
    return (
        <AppLayout title={`${vps.hostname} — Firewall`}>
            <Head title={`Firewall — ${vps.hostname}`} />

            <Card>
                <CardHeader>
                    <CardTitle>Firewall Rules</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Direction</TableHead>
                                <TableHead>Protocol</TableHead>
                                <TableHead>Port</TableHead>
                                <TableHead>Source/Dest</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rules.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-gray-400 py-8">
                                        No firewall rules configured.
                                    </TableCell>
                                </TableRow>
                            )}
                            {rules.map((rule, i) => (
                                <TableRow key={i}>
                                    <TableCell>
                                        <Badge variant={rule.direction === 'inbound' ? 'info' : 'default'}>
                                            {rule.direction}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="uppercase text-xs font-mono">{rule.protocol}</TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {rule.port_range_min}{rule.port_range_max !== rule.port_range_min ? `–${rule.port_range_max}` : ''}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {rule.direction === 'inbound' ? rule.source : rule.destination}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={rule.action === 'accept' ? 'success' : 'destructive'}>
                                            {rule.action}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
