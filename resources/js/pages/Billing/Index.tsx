import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { BillingItem, Subscription } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

interface PaymentMethod {
    id: string;
    type: string;
    last4?: string;
    brand?: string;
    is_default: boolean;
}

interface Props {
    catalog: BillingItem[];
    paymentMethods: PaymentMethod[];
    subscriptions: Subscription[];
}

type Tab = 'subscriptions' | 'catalog' | 'payment';

export default function BillingIndex({ catalog, paymentMethods, subscriptions }: Props) {
    const [tab, setTab] = useState<Tab>('subscriptions');

    const tabs: { key: Tab; label: string }[] = [
        { key: 'subscriptions', label: 'Subscriptions' },
        { key: 'catalog', label: 'Catalog' },
        { key: 'payment', label: 'Payment Methods' },
    ];

    function statusVariant(status: string): 'success' | 'warning' | 'destructive' | 'default' {
        if (status === 'active') return 'success';
        if (status === 'trialing') return 'info' as 'default';
        if (status === 'past_due') return 'warning';
        if (status === 'cancelled') return 'destructive';
        return 'default';
    }

    return (
        <AppLayout title="Billing">
            <Head title="Billing" />

            <div className="mb-6 flex gap-1 border-b border-gray-200">
                {tabs.map(({ key, label }) => (
                    <button
                        key={key}
                        onClick={() => setTab(key)}
                        className={`px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px ${
                            tab === key
                                ? 'border-gray-900 text-gray-900'
                                : 'border-transparent text-gray-500 hover:text-gray-900'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'subscriptions' && (
                <Card>
                    <CardHeader><CardTitle>Active Subscriptions</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Billing cycle</TableHead>
                                    <TableHead>Next billing</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {subscriptions.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-gray-400 py-8">No subscriptions.</TableCell>
                                    </TableRow>
                                )}
                                {subscriptions.map((s) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="font-medium text-gray-900">{s.name}</TableCell>
                                        <TableCell><Badge variant={statusVariant(s.status)}>{s.status}</Badge></TableCell>
                                        <TableCell className="text-gray-700">{s.price} {s.currency}</TableCell>
                                        <TableCell className="text-gray-500">{s.billing_cycle}</TableCell>
                                        <TableCell className="text-gray-500">{s.next_billing_date ?? '—'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}

            {tab === 'catalog' && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {catalog.length === 0 && (
                        <p className="text-sm text-gray-400 sm:col-span-3">No catalog items.</p>
                    )}
                    {catalog.map((item) => (
                        <Card key={item.id}>
                            <CardHeader>
                                <CardTitle className="text-sm">{item.name}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold text-gray-900">
                                    {item.price} <span className="text-sm font-normal text-gray-400">{item.currency}</span>
                                </p>
                                <p className="text-xs text-gray-500 mt-1">{item.billing_cycle}</p>
                                {item.description && <p className="text-sm text-gray-600 mt-2">{item.description}</p>}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {tab === 'payment' && (
                <Card>
                    <CardHeader><CardTitle>Payment Methods</CardTitle></CardHeader>
                    <CardContent>
                        {paymentMethods.length === 0 && (
                            <p className="text-sm text-gray-400">No payment methods on file.</p>
                        )}
                        <div className="space-y-3">
                            {paymentMethods.map((pm) => (
                                <div key={pm.id} className="flex items-center justify-between rounded-md border border-gray-200 p-4">
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm font-medium text-gray-900 capitalize">{pm.brand ?? pm.type}</span>
                                        {pm.last4 && <span className="text-sm text-gray-500">····{pm.last4}</span>}
                                    </div>
                                    {pm.is_default && <Badge variant="success">Default</Badge>}
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}
        </AppLayout>
    );
}
