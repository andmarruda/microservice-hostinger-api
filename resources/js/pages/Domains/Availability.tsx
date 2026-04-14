import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import AppLayout from '@/layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface AvailabilityResult {
    domain: string;
    available: boolean;
    premium: boolean;
    price?: number;
    currency?: string;
}

interface Props {
    result: AvailabilityResult | null;
    query: string | null;
}

export default function DomainsAvailability({ result, query }: Props) {
    const [domain, setDomain] = useState(query ?? '');

    function handleCheck(e: FormEvent) {
        e.preventDefault();
        router.get('/domains/check', { domain }, { preserveState: true });
    }

    return (
        <AppLayout title="Domain Availability">
            <Head title="Domain Availability" />

            <Card className="mb-6 max-w-lg">
                <CardHeader><CardTitle>Check domain availability</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={handleCheck} className="flex gap-2">
                        <div className="flex-1 space-y-1.5">
                            <Label htmlFor="domain">Domain name</Label>
                            <Input
                                id="domain"
                                value={domain}
                                onChange={(e) => setDomain(e.target.value)}
                                placeholder="example.com"
                            />
                        </div>
                        <div className="flex items-end">
                            <Button type="submit">Check</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {result && (
                <Card className="max-w-lg">
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <span className="text-lg font-medium text-gray-900">{result.domain}</span>
                            <Badge variant={result.available ? 'success' : 'destructive'}>
                                {result.available ? 'Available' : 'Taken'}
                            </Badge>
                        </div>
                        {result.available && result.price && (
                            <p className="mt-2 text-sm text-gray-500">
                                Price: {result.price} {result.currency}
                                {result.premium && <span className="ml-2 text-yellow-600">(Premium)</span>}
                            </p>
                        )}
                    </CardContent>
                </Card>
            )}
        </AppLayout>
    );
}
