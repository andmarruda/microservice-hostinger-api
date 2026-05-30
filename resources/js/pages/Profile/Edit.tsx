import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import AppLayout from '@/layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
    };
}

export default function ProfileEdit({ user }: Props) {
    const nameForm = useForm({ name: user.name });
    const passForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function handleNameUpdate(e: FormEvent) {
        e.preventDefault();
        nameForm.put('/profile');
    }

    function handlePasswordUpdate(e: FormEvent) {
        e.preventDefault();
        passForm.put('/profile/password', {
            onSuccess: () => passForm.reset(),
        });
    }

    return (
        <AppLayout title="Profile">
            <Head title="Profile" />

            <div className="mx-auto max-w-xl space-y-8">
                {/* Name / Email */}
                <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-base font-semibold text-gray-900">Profile Information</h2>
                    <form onSubmit={handleNameUpdate} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                value={nameForm.data.name}
                                onChange={(e) => nameForm.setData('name', e.target.value)}
                            />
                            {nameForm.errors.name && (
                                <p className="text-xs text-red-600">{nameForm.errors.name}</p>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Email</Label>
                            <Input type="email" value={user.email} disabled className="bg-gray-50 text-gray-500" />
                            <p className="text-xs text-gray-400">Email cannot be changed.</p>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" disabled={nameForm.processing}>
                                {nameForm.processing ? 'Saving…' : 'Save'}
                            </Button>
                        </div>
                    </form>
                </div>

                {/* Password */}
                <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-base font-semibold text-gray-900">Change Password</h2>
                    <form onSubmit={handlePasswordUpdate} className="space-y-4">
                        {passForm.errors.current_password && (
                            <Alert variant="destructive">{passForm.errors.current_password}</Alert>
                        )}
                        <div className="space-y-1.5">
                            <Label htmlFor="current-password">Current Password</Label>
                            <Input
                                id="current-password"
                                type="password"
                                autoComplete="current-password"
                                value={passForm.data.current_password}
                                onChange={(e) => passForm.setData('current_password', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="new-password">New Password</Label>
                            <Input
                                id="new-password"
                                type="password"
                                autoComplete="new-password"
                                value={passForm.data.password}
                                onChange={(e) => passForm.setData('password', e.target.value)}
                            />
                            {passForm.errors.password && (
                                <p className="text-xs text-red-600">{passForm.errors.password}</p>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="confirm-password">Confirm New Password</Label>
                            <Input
                                id="confirm-password"
                                type="password"
                                autoComplete="new-password"
                                value={passForm.data.password_confirmation}
                                onChange={(e) => passForm.setData('password_confirmation', e.target.value)}
                            />
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" disabled={passForm.processing}>
                                {passForm.processing ? 'Updating…' : 'Update Password'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
