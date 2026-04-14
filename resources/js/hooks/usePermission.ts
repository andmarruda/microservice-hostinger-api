import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

export function usePermission() {
    const { auth } = usePage<SharedData>().props;

    return {
        can: (permission: string): boolean => auth.permissions.includes(permission),
        is:  (role: string): boolean => auth.roles.includes(role),
        isRoot: (): boolean => auth.roles.includes('root'),
    };
}
