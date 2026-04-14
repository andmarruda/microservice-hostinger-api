import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { CacheKeyStats } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    stats: CacheKeyStats[];
}

export default function OpsCache({ stats }: Props) {
    return (
        <AppLayout title="Cache Stats">
            <Head title="Cache Stats" />

            <Card>
                <CardHeader><CardTitle>Cache Hit/Miss by Key</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Cache Key</TableHead>
                                <TableHead className="text-right">Hits</TableHead>
                                <TableHead className="text-right">Misses</TableHead>
                                <TableHead className="text-right">Hit Rate</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {stats.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-center text-gray-400 py-8">
                                        No cache stats recorded yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {stats.map((s) => {
                                const total = s.hits + s.misses;
                                const rate = total > 0 ? Math.round((s.hits / total) * 100) : 0;
                                return (
                                    <TableRow key={s.key}>
                                        <TableCell className="font-mono text-xs">{s.key}</TableCell>
                                        <TableCell className="text-right text-green-600 font-medium">{s.hits}</TableCell>
                                        <TableCell className="text-right text-red-500 font-medium">{s.misses}</TableCell>
                                        <TableCell className="text-right">
                                            <span className={`font-medium ${rate >= 80 ? 'text-green-600' : rate >= 50 ? 'text-yellow-600' : 'text-red-500'}`}>
                                                {rate}%
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
