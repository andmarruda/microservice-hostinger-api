import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { usePermission } from '@/hooks/usePermission';
import AppLayout from '@/layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

interface Quota {
    total: number;
    warn_at: number;
    hard_limit: number;
    by_resource: Record<string, number>;
    percent: number;
    status: 'ok' | 'warning' | 'exceeded';
}

interface Props {
    vpsCount: number;
    openReviews: number | null;
    pendingApprovals: number | null;
    openDriftReports: number | null;
    queuePending: number | null;
    queueFailed: number | null;
    quota: Quota | null;
}

interface MetricCardProps {
    label: string;
    value: string | number;
    sub?: string;
    tone?: 'default' | 'warning' | 'danger' | 'success';
}

const toneClasses = {
    default: 'text-gray-900',
    warning: 'text-yellow-700',
    danger: 'text-red-700',
    success: 'text-green-700',
};

function MetricCard({ label, value, sub, tone = 'default' }: MetricCardProps) {
    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium text-gray-500">{label}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className={`text-3xl font-bold ${toneClasses[tone]}`}>{value}</p>
                {sub && <p className="mt-1 text-xs text-gray-400">{sub}</p>}
            </CardContent>
        </Card>
    );
}

function ModuleShortcut({ title, href, description }: { title: string; href: string; description: string }) {
    return (
        <Link
            href={href}
            className="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
        >
            <div className="flex items-center justify-between gap-3">
                <span className="text-sm font-semibold text-gray-900">{title}</span>
                <span className="text-xs font-medium text-gray-400">Open</span>
            </div>
            <p className="mt-2 text-sm leading-5 text-gray-500">{description}</p>
        </Link>
    );
}

function quotaTone(status: Quota['status']) {
    if (status === 'exceeded') return 'danger';
    if (status === 'warning') return 'warning';
    return 'success';
}

function quotaBadge(status: Quota['status']) {
    if (status === 'exceeded') return 'destructive';
    if (status === 'warning') return 'warning';
    return 'success';
}

export default function Dashboard({ vpsCount, openReviews, pendingApprovals, openDriftReports, queuePending, queueFailed, quota }: Props) {
    const { can, isAdmin } = usePermission();
    const root = isAdmin();
    const canAny = (permissions: string[]) => permissions.some((permission) => can(permission));

    const shortcuts = [
        {
            title: 'VPS',
            href: '/vps',
            description: 'Servers, metrics, lifecycle actions, backups, firewall, SSH keys, and snapshots.',
            show: can('VPS.VirtualMachine.Manage.read'),
        },
        {
            title: 'Domains',
            href: '/domains',
            description: 'Portfolio, availability checks, lock state, privacy, and nameserver operations.',
            show: canAny(['Domains.Portfolio.Manage.read', 'Domains.Portfolio.Details', 'Domains.Availability.validate']),
        },
        {
            title: 'DNS',
            href: '/dns/example.com',
            description: 'Zone records, validation, snapshots, restore paths, and guarded DNS changes.',
            show: can('DNS.Zone.read'),
        },
        {
            title: 'Billing',
            href: '/billing',
            description: 'Catalog, payment methods, subscriptions, and account commercial visibility.',
            show: can('Billing.getCatalog') || can('Orders.Subscriptions.read'),
        },
        {
            title: 'Governance',
            href: '/governance/reviews',
            description: 'Access reviews, approvals, audit exports, and permission oversight.',
            show: root,
        },
        {
            title: 'Operations',
            href: '/ops/health',
            description: 'Health checks, quota pressure, cache behavior, queue state, and database stats.',
            show: root,
        },
    ].filter((item) => item.show);

    const quotaPct = quota ? Math.min(Math.round(quota.percent), 100) : 0;
    const queueFailedTone = (queueFailed ?? 0) > 0 ? 'danger' : 'default';

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {can('VPS.VirtualMachine.Manage.read') && (
                        <MetricCard label="VPS Instances" value={vpsCount} sub="visible in your resource scope" />
                    )}

                    {root && (
                        <>
                            <MetricCard
                                label="Open Drift Reports"
                                value={openDriftReports ?? 0}
                                sub="infrastructure drift"
                                tone={(openDriftReports ?? 0) > 0 ? 'warning' : 'success'}
                            />
                            <MetricCard
                                label="Pending Approvals"
                                value={pendingApprovals ?? 0}
                                sub="awaiting root review"
                                tone={(pendingApprovals ?? 0) > 0 ? 'warning' : 'success'}
                            />
                            <MetricCard label="Open Access Reviews" value={openReviews ?? 0} sub="governance reviews" />
                        </>
                    )}
                </div>

                <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <section>
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <h2 className="text-sm font-semibold text-gray-900">Available Workspaces</h2>
                            <Badge variant="outline">{shortcuts.length} enabled</Badge>
                        </div>

                        {shortcuts.length > 0 ? (
                            <div className="grid gap-3 md:grid-cols-2">
                                {shortcuts.map((shortcut) => (
                                    <ModuleShortcut key={shortcut.href} {...shortcut} />
                                ))}
                            </div>
                        ) : (
                            <Card>
                                <CardContent className="p-6">
                                    <p className="text-sm text-gray-500">Your account is active, but no infrastructure workspace is enabled yet.</p>
                                </CardContent>
                            </Card>
                        )}
                    </section>

                    {root && (
                        <aside className="space-y-4">
                            <Card>
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between gap-3">
                                        <CardTitle className="text-sm font-medium text-gray-500">Resource Quota</CardTitle>
                                        {quota && <Badge variant={quotaBadge(quota.status)}>{quota.status}</Badge>}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {quota ? (
                                        <>
                                            <div className="mb-2 flex items-end justify-between gap-3">
                                                <span className={`text-3xl font-bold ${toneClasses[quotaTone(quota.status)]}`}>{quotaPct}%</span>
                                                <span className="text-xs text-gray-400">
                                                    {quota.total} / {quota.warn_at} warning
                                                </span>
                                            </div>
                                            <div className="h-2 w-full rounded-full bg-gray-100">
                                                <div
                                                    className={`h-2 rounded-full transition-all ${
                                                        quota.status === 'exceeded'
                                                            ? 'bg-red-500'
                                                            : quota.status === 'warning'
                                                              ? 'bg-yellow-500'
                                                              : 'bg-green-500'
                                                    }`}
                                                    style={{ width: `${quotaPct}%` }}
                                                />
                                            </div>
                                            <div className="mt-4 space-y-2">
                                                {Object.entries(quota.by_resource)
                                                    .slice(0, 5)
                                                    .map(([resource, value]) => (
                                                        <div key={resource} className="flex items-center justify-between text-sm">
                                                            <span className="truncate text-gray-500">{resource}</span>
                                                            <span className="font-medium text-gray-900">{value}</span>
                                                        </div>
                                                    ))}
                                            </div>
                                        </>
                                    ) : (
                                        <p className="text-sm text-gray-500">Quota telemetry is unavailable.</p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium text-gray-500">Queue Health</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-gray-600">Pending jobs</span>
                                        <span className="font-medium text-gray-900">{queuePending ?? 0}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-gray-600">Failed jobs</span>
                                        <span className={`font-medium ${toneClasses[queueFailedTone]}`}>{queueFailed ?? 0}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </aside>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
