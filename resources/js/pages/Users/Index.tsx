import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/Dialog';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Select } from '@/components/ui/Select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import AppLayout from '@/layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface UserRow {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    role: string;
    created_at: string;
}

interface Props {
    users: UserRow[];
}

export default function UsersIndex({ users }: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'user' as 'admin' | 'user',
    });

    function handleCreate(e: FormEvent) {
        e.preventDefault();
        createForm.post('/users', {
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
            },
        });
    }

    return (
        <AppLayout title="Users">
            <Head title="Users" />

            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">Users</h2>
                <Link href="/users/create">
                    <Button>Create User</Button>
                </Link>
            </div>

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="py-8 text-center text-gray-400">
                                    No users found.
                                </TableCell>
                            </TableRow>
                        )}
                        {users.map((user) => (
                            <TableRow key={user.id}>
                                <TableCell className="font-medium text-gray-900">{user.name}</TableCell>
                                <TableCell>{user.email}</TableCell>
                                <TableCell>
                                    <Badge variant={user.email_verified_at ? 'success' : 'warning'}>
                                        {user.email_verified_at ? 'Active' : 'Unverified'}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={user.role === 'admin' ? 'info' : 'default'}>
                                        {user.role === 'admin' ? 'Admin' : 'User'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-xs text-gray-500">
                                    {new Date(user.created_at).toLocaleDateString()}
                                </TableCell>
                                <TableCell>
                                    <Link
                                        href={`/users/${user.id}`}
                                        className="text-xs text-blue-600 hover:underline"
                                    >
                                        Manage
                                    </Link>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* Create User Dialog (root only) */}
            <Dialog open={createOpen} onClose={() => setCreateOpen(false)} className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create User</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleCreate}>
                    <DialogContent className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="create-name">Name</Label>
                            <Input
                                id="create-name"
                                type="text"
                                autoComplete="off"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                placeholder="Full name"
                            />
                            {createForm.errors.name && (
                                <p className="text-xs text-red-600">{createForm.errors.name}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="create-email">Email</Label>
                            <Input
                                id="create-email"
                                type="email"
                                autoComplete="off"
                                value={createForm.data.email}
                                onChange={(e) => createForm.setData('email', e.target.value)}
                                placeholder="user@example.com"
                            />
                            {createForm.errors.email && (
                                <p className="text-xs text-red-600">{createForm.errors.email}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="create-password">Password</Label>
                            <Input
                                id="create-password"
                                type="password"
                                autoComplete="new-password"
                                value={createForm.data.password}
                                onChange={(e) => createForm.setData('password', e.target.value)}
                            />
                            {createForm.errors.password && (
                                <p className="text-xs text-red-600">{createForm.errors.password}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="create-password-confirmation">Confirm Password</Label>
                            <Input
                                id="create-password-confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={createForm.data.password_confirmation}
                                onChange={(e) => createForm.setData('password_confirmation', e.target.value)}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="create-role">Role</Label>
                            <Select
                                id="create-role"
                                value={createForm.data.role}
                                onChange={(e) => createForm.setData('role', e.target.value as 'admin' | 'user')}
                            >
                                <option value="user">User — limited to granted VPS</option>
                                <option value="admin">Admin — full access</option>
                            </Select>
                            {createForm.errors.role && (
                                <p className="text-xs text-red-600">{createForm.errors.role}</p>
                            )}
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={createForm.processing}>
                            {createForm.processing ? 'Creating…' : 'Create User'}
                        </Button>
                    </DialogFooter>
                </form>
            </Dialog>
        </AppLayout>
    );
}
