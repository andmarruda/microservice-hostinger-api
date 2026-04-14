import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import AppLayout from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';

interface Props {
    vpsCount: number;
    openReviews: number;
    pendingApprovals: number;
    openDriftReports: number;
    queuePending: number;
    queueFailed: number;
    quotaUsed: number;
    quotaLimit: number;
}

function StatCard({ label, value, sub }: { label: string; value: string | number; sub?: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm font-medium text-gray-500">{label}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-3xl font-bold text-gray-900">{value}</p>
                {sub && <p className="mt-1 text-xs text-gray-400">{sub}</p>}
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    vpsCount,
    openReviews,
    pendingApprovals,
    openDriftReports,
    queuePending,
    queueFailed,
    quotaUsed,
    quotaLimit,
}: Props) {
    const quotaPct = quotaLimit > 0 ? Math.round((quotaUsed / quotaLimit) * 100) : 0;

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="VPS Instances" value={vpsCount} />
                <StatCard label="Open Drift Reports" value={openDriftReports} sub="infrastructure drift" />
                <StatCard label="Pending Approvals" value={pendingApprovals} sub="awaiting root review" />
                <StatCard label="Open Access Reviews" value={openReviews} sub="governance reviews" />
            </div>

            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-gray-500">Queue Health</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-gray-600">Pending jobs</span>
                            <span className="font-medium text-gray-900">{queuePending}</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-gray-600">Failed jobs</span>
                            <span className={`font-medium ${queueFailed > 0 ? 'text-red-600' : 'text-gray-900'}`}>
                                {queueFailed}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-gray-500">Resource Quota</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-end justify-between mb-2">
                            <span className="text-3xl font-bold text-gray-900">{quotaPct}%</span>
                            <span className="text-xs text-gray-400">{quotaUsed} / {quotaLimit} used</span>
                        </div>
                        <div className="h-2 w-full rounded-full bg-gray-100">
                            <div
                                className={`h-2 rounded-full transition-all ${
                                    quotaPct >= 90 ? 'bg-red-500' : quotaPct >= 70 ? 'bg-yellow-500' : 'bg-green-500'
                                }`}
                                style={{ width: `${Math.min(quotaPct, 100)}%` }}
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
