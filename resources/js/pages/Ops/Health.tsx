import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import AppLayout from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';

interface ServiceHealth {
    name: string;
    status: 'ok' | 'degraded' | 'down';
    message?: string;
}

interface Props {
    services: ServiceHealth[];
    checkedAt: string;
}

function statusVariant(status: string): 'success' | 'warning' | 'destructive' {
    if (status === 'ok') return 'success';
    if (status === 'degraded') return 'warning';
    return 'destructive';
}

export default function OpsHealth({ services, checkedAt }: Props) {
    const allOk = services.every((s) => s.status === 'ok');

    return (
        <AppLayout title="System Health">
            <Head title="System Health" />

            <div className="mb-4 flex items-center gap-3">
                <Badge variant={allOk ? 'success' : 'destructive'} className="text-sm px-3 py-1">
                    {allOk ? 'All systems operational' : 'Degraded service'}
                </Badge>
                <span className="text-xs text-gray-400">Checked at {checkedAt}</span>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {services.map((svc) => (
                    <Card key={svc.name}>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-sm">
                                {svc.name}
                                <Badge variant={statusVariant(svc.status)}>{svc.status}</Badge>
                            </CardTitle>
                        </CardHeader>
                        {svc.message && (
                            <CardContent>
                                <p className="text-xs text-gray-500">{svc.message}</p>
                            </CardContent>
                        )}
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
