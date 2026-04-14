import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import AppLayout from '@/layouts/AppLayout';
import { QuotaStats } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    quota: QuotaStats;
}

function QuotaRow({ label, used, limit }: { label: string; used: number; limit: number }) {
    const pct = limit > 0 ? Math.round((used / limit) * 100) : 0;
    return (
        <div>
            <div className="flex justify-between text-sm mb-1">
                <span className="text-gray-700">{label}</span>
                <span className="text-gray-500">{used} / {limit} ({pct}%)</span>
            </div>
            <div className="h-2 w-full rounded-full bg-gray-100">
                <div
                    className={`h-2 rounded-full ${pct >= 90 ? 'bg-red-500' : pct >= 70 ? 'bg-yellow-500' : 'bg-green-500'}`}
                    style={{ width: `${Math.min(pct, 100)}%` }}
                />
            </div>
        </div>
    );
}

export default function OpsQuota({ quota }: Props) {
    return (
        <AppLayout title="Resource Quota">
            <Head title="Resource Quota" />

            <Card>
                <CardHeader><CardTitle>Quota Usage</CardTitle></CardHeader>
                <CardContent className="space-y-5">
                    <QuotaRow label="VPS Instances" used={quota.vps_used} limit={quota.vps_limit} />
                    <QuotaRow label="Domains" used={quota.domains_used} limit={quota.domains_limit} />
                    <QuotaRow label="DNS Records" used={quota.dns_records_used} limit={quota.dns_records_limit} />
                    <QuotaRow label="Snapshots" used={quota.snapshots_used} limit={quota.snapshots_limit} />
                    <QuotaRow label="SSH Keys" used={quota.ssh_keys_used} limit={quota.ssh_keys_limit} />
                    <QuotaRow label="Firewall Rules" used={quota.firewall_rules_used} limit={quota.firewall_rules_limit} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
