import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';

interface TableInfo {
    name: string;
    rows: number;
    retention_days: number | null;
}

interface Props {
    tables: TableInfo[];
}

export default function OpsDatabase({ tables }: Props) {
    return (
        <AppLayout title="Database">
            <Head title="Database" />

            <Card>
                <CardHeader><CardTitle>Table Row Counts & Retention</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Table</TableHead>
                                <TableHead className="text-right">Rows</TableHead>
                                <TableHead className="text-right">Retention (days)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tables.map((t) => (
                                <TableRow key={t.name}>
                                    <TableCell className="font-mono text-sm">{t.name}</TableCell>
                                    <TableCell className="text-right font-medium text-gray-900">
                                        {t.rows.toLocaleString()}
                                    </TableCell>
                                    <TableCell className="text-right text-gray-500">
                                        {t.retention_days ?? '∞'}
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
