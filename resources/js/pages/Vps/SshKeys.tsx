import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import AppLayout from '@/layouts/AppLayout';
import { SshKey, Vps } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    vps: Vps;
    keys: SshKey[];
}

export default function VpsSshKeys({ vps, keys }: Props) {
    return (
        <AppLayout title={`${vps.hostname} — SSH Keys`}>
            <Head title={`SSH Keys — ${vps.hostname}`} />

            <Card>
                <CardHeader>
                    <CardTitle>SSH Keys</CardTitle>
                </CardHeader>
                <CardContent>
                    {keys.length === 0 ? (
                        <p className="text-sm text-gray-400">No SSH keys associated with this VPS.</p>
                    ) : (
                        <div className="space-y-3">
                            {keys.map((key) => (
                                <div key={key.id} className="rounded-md border border-gray-200 p-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="font-medium text-gray-900">{key.name}</span>
                                        <span className="text-xs text-gray-400">Added {key.created_at}</span>
                                    </div>
                                    <p className="font-mono text-xs text-gray-500 break-all">{key.fingerprint}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
