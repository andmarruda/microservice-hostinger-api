import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function usePermission() {
    const { auth } = usePage<SharedData>().props;
    const isAdmin = auth.roles.includes('admin');

    return {
        can: (permission: string): boolean => isAdmin || auth.permissions.includes(permission),
        is: (role: string): boolean => auth.roles.includes(role),
        isAdmin: (): boolean => isAdmin,
    };
}
