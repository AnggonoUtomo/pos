import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function usePermission() {
    const { auth } = usePage<SharedData>().props;

    const permissions = auth.permissions ?? {};
    const roles = auth.roles ?? {};
    const isSuperAdmin = auth.super === true;

    const can = (permission: string): boolean => {
        return isSuperAdmin || permissions[permission] === true;
    };

    const canAny = (permissionList: string[]): boolean => {
        return isSuperAdmin || permissionList.some((permission) => permissions[permission] === true);
    };

    const hasRole = (role: string): boolean => {
        return roles[role] === true;
    };

    return {
        user: auth.user,
        roles,
        permissions,
        isSuperAdmin,
        can,
        canAny,
        hasRole,
    };
}
