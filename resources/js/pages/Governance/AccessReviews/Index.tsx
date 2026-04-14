import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { AccessReview } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    reviews: AccessReview[];
}

function statusVariant(status: string): 'default' | 'warning' | 'success' | 'destructive' {
    if (status === 'pending') return 'warning';
    if (status === 'completed') return 'success';
    if (status === 'cancelled') return 'destructive';
    return 'default';
}

export default function AccessReviewsIndex({ reviews }: Props) {
    const { post, processing } = useForm({});

    return (
        <AppLayout title="Access Reviews">
            <Head title="Access Reviews" />

            <div className="mb-4 flex justify-end">
                <Button onClick={() => post('/governance/reviews')} disabled={processing}>
                    New Review
                </Button>
            </div>

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Reviewer</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Items</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {reviews.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center text-gray-400 py-8">
                                    No access reviews found.
                                </TableCell>
                            </TableRow>
                        )}
                        {reviews.map((r) => (
                            <TableRow key={r.id}>
                                <TableCell className="font-mono text-xs text-gray-500">{r.id.slice(0, 8)}</TableCell>
                                <TableCell className="text-gray-900">{r.reviewer_id}</TableCell>
                                <TableCell>
                                    <Badge variant={statusVariant(r.status)}>{r.status}</Badge>
                                </TableCell>
                                <TableCell className="text-gray-500">{r.items?.length ?? 0}</TableCell>
                                <TableCell className="text-gray-500">{r.created_at}</TableCell>
                                <TableCell>
                                    <Link
                                        href={`/governance/reviews/${r.id}`}
                                        className="text-sm text-gray-500 hover:text-gray-900 hover:underline"
                                    >
                                        View
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
