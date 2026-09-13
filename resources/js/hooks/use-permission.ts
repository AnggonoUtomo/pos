import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function usePermission() {
    const { auth } = usePage<SharedData>().props;

    const permissions = auth.permissions ?? {};
    const roles = auth.roles ?? {};
    const isSuperSystem = auth.superSystem === true;

    const can = (permission: string): boolean => {
        return isSuperSystem || permissions[permission] === true;
    };

    const canAny = (permissionList: string[]): boolean => {
        return isSuperSystem || permissionList.some((permission) => permissions[permission] === true);
    };

    const hasRole = (role: string): boolean => {
        return roles[role] === true;
    };

    return {
        user: auth.user,
        roles,
        permissions,
        isSuperSystem,
        can,
        canAny,
        hasRole,
    };
}
