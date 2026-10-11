export type AccessTab = 'roles' | 'permissions' | 'users';
export interface AccessRow {
    id: number;
    name: string;
    permissions?: string[];
    users_count?: number;
    roles_count?: number;
    email?: string;
    roles?: string[];
    direct_permissions_count?: number;
    is_active?: boolean;
    can_edit?: boolean;
}
export interface RoleRow extends AccessRow {
    permissions: string[];
    users_count: number;
}
export interface UserRow extends AccessRow {
    email: string;
    roles: string[];
    is_active: boolean;
}
export interface AccessPageProps {
    records: { data: AccessRow[]; meta: { current_page: number; last_page: number; total: number; per_page: number } };
    filters: { tab: AccessTab; search: string; page: number };
    options: { roles: RoleRow[]; permissions: string[]; active_super_users: number };
}
export type AccessEditor = { kind: 'role'; record: RoleRow | null } | { kind: 'permissions'; record: RoleRow } | { kind: 'user'; record: UserRow };
export const reservedRole = (name: string) => ['super-system', 'super-admin'].includes(name.toLowerCase());
