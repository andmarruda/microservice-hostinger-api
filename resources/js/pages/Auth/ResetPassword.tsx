import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    token: string;
    email?: string;
}

export default function ResetPassword({ token, email = '' }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/reset-password');
    }

    return (
        <>
            <Head title="Reset password" />

            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-6 py-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <h1 className="text-2xl font-bold text-gray-900">Hostinger</h1>
                        <p className="mt-1 text-sm text-gray-500">Choose a new password</p>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
                        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                            <div className="space-y-1.5">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="you@company.com"
                                />
                                {errors.email && <p className="text-xs text-red-600">{errors.email}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="password">New password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password && <p className="text-xs text-red-600">{errors.password}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="password_confirmation">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                />
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? 'Resetting password...' : 'Reset password'}
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
