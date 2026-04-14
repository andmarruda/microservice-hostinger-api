import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { AccessReview, AccessReviewItem } from '@/types';
import { Head, router } from '@inertiajs/react';

interface Props {
    review: AccessReview & { items: AccessReviewItem[] };
}

function decisionVariant(decision: string | null): 'success' | 'destructive' | 'default' {
    if (decision === 'approved') return 'success';
    if (decision === 'revoked') return 'destructive';
    return 'default';
}

function statusVariant(status: string): 'default' | 'warning' | 'success' | 'destructive' {
    if (status === 'pending') return 'warning';
    if (status === 'completed') return 'success';
    if (status === 'cancelled') return 'destructive';
    return 'default';
}

export default function AccessReviewShow({ review }: Props) {
    function decide(itemId: string, decision: 'approved' | 'revoked') {
        router.post(`/governance/reviews/${review.id}/items/${itemId}`, { decision });
    }

    return (
        <AppLayout title="Access Review">
            <Head title="Access Review" />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-3">
                        Review <Badge variant={statusVariant(review.status)}>{review.status}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm">
                    <div className="flex gap-8">
                        <div>
                            <span className="text-gray-500">Reviewer: </span>
                            <span className="font-medium">{review.reviewer_id}</span>
                        </div>
                        <div>
                            <span className="text-gray-500">Created: </span>
                            <span className="font-medium">{review.created_at}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>User</TableHead>
                            <TableHead>VPS</TableHead>
                            <TableHead>Granted</TableHead>
                            <TableHead>Expires</TableHead>
                            <TableHead>Decision</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {review.items.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center text-gray-400 py-8">
                                    No items in this review.
                                </TableCell>
                            </TableRow>
                        )}
                        {review.items.map((item) => (
                            <TableRow key={item.id}>
                                <TableCell className="font-medium text-gray-900">{item.user_id}</TableCell>
                                <TableCell className="text-gray-500">{item.vps_id}</TableCell>
                                <TableCell className="text-gray-500">{item.granted_at}</TableCell>
                                <TableCell className="text-gray-500">{item.expires_at ?? '—'}</TableCell>
                                <TableCell>
                                    {item.decision ? (
                                        <Badge variant={decisionVariant(item.decision)}>{item.decision}</Badge>
                                    ) : (
                                        <Badge variant="default">pending</Badge>
                                    )}
                                </TableCell>
                                <TableCell>
                                    {!item.decision && review.status === 'pending' && (
                                        <div className="flex gap-1.5">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => decide(item.id, 'approved')}
                                            >
                                                Approve
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => decide(item.id, 'revoked')}
                                            >
                                                Revoke
                                            </Button>
                                        </div>
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
