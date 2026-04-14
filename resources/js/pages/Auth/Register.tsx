import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    token: string;
}

export default function Register({ token }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        name: '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/register');
    }

    return (
        <>
            <Head title="Create account" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <h1 className="text-2xl font-bold text-gray-900">Hostinger</h1>
                        <p className="mt-1 text-sm text-gray-500">Create your account</p>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    autoComplete="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Your full name"
                                />
                                {errors.name && (
                                    <p className="text-xs text-red-600">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password && (
                                    <p className="text-xs text-red-600">{errors.password}</p>
                                )}
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
                                {processing ? 'Creating account…' : 'Create account'}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
