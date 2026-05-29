import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function usePermission() {
    const { auth } = usePage<SharedData>().props;
    const isRoot = auth.roles.includes('root');

    return {
        can: (permission: string): boolean => isRoot || auth.permissions.includes(permission),
        is: (role: string): boolean => auth.roles.includes(role),
        isRoot: (): boolean => isRoot,
    };
}
