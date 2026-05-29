import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

interface SharedProps {
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}

export default function ForgotPassword() {
    const { flash } = usePage<SharedProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/forgot-password');
    }

    return (
        <>
            <Head title="Forgot password" />

            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-6 py-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <h1 className="text-2xl font-bold text-gray-900">Hostinger</h1>
                        <p className="mt-1 text-sm text-gray-500">Reset access to your console</p>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
                        {flash?.success && <p className="mb-4 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{flash.success}</p>}

                        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                            <div className="space-y-1.5">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="you@company.com"
                                />
                                {errors.email && <p className="text-xs text-red-600">{errors.email}</p>}
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? 'Sending link...' : 'Send reset link'}
                            </Button>
                        </form>

                        <div className="mt-6 text-center">
                            <Link href="/login" className="text-sm font-medium text-gray-700 hover:text-gray-900">
                                Back to login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
