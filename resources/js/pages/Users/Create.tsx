import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Select } from '@/components/ui/Select';
import AppLayout from '@/layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function UsersCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'user' as 'admin' | 'user',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/users');
    }

    return (
        <AppLayout title="Create User">
            <Head title="Create User" />

            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Create User</h2>
                <Link href="/users" className="text-sm text-gray-500 hover:text-gray-900">
                    ← Back to users
                </Link>
            </div>

            <Card className="max-w-lg">
                <CardHeader>
                    <CardTitle>New user account</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                autoComplete="off"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Full name"
                            />
                            {errors.name && <p className="text-xs text-red-600">{errors.name}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                autoComplete="off"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="user@example.com"
                            />
                            {errors.email && <p className="text-xs text-red-600">{errors.email}</p>}
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

                        <div className="space-y-1.5">
                            <Label htmlFor="role">Role</Label>
                            <Select
                                id="role"
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value as 'admin' | 'user')}
                            >
                                <option value="user">User — limited to granted VPS</option>
                                <option value="admin">Admin — full access</option>
                            </Select>
                            {errors.role && <p className="text-xs text-red-600">{errors.role}</p>}
                        </div>

                        <div className="flex gap-3 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creating…' : 'Create User'}
                            </Button>
                            <Link href="/users">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
