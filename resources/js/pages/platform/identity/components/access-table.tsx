import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { KeyRound, LockKeyhole, Pencil, ShieldCheck, Trash2, UserCog } from 'lucide-react';
import type { AccessEditor, AccessRow, AccessTab, RoleRow } from '../types';
import { reservedRole } from '../types';

export function AccessTable({
    rows,
    tab,
    canManage,
    isSuperSystem,
    canGrant,
    onEdit,
    onDelete,
}: {
    rows: AccessRow[];
    tab: AccessTab;
    canManage: boolean;
    isSuperSystem: boolean;
    canGrant: (name: string) => boolean;
    onEdit: (editor: AccessEditor) => void;
    onDelete: (role: RoleRow) => void;
}) {
    const operation = (label: string, icon: React.ReactNode, action: () => void) => (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={label} onClick={action}>
                    {icon}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
    return (
        <table className="w-full text-sm">
            <thead className="bg-muted/40 text-muted-foreground border-b">
                <tr>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        {tab === 'permissions' ? 'Key permission' : tab === 'users' ? 'Pengguna' : 'Role'}
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        {tab === 'roles' ? 'Permission' : tab === 'permissions' ? 'Role terkait' : 'Role pengguna'}
                    </th>
                    {tab === 'roles' && (
                        <th scope="col" className="px-4 py-3 text-left font-medium">
                            Pengguna
                        </th>
                    )}
                    {canManage && tab !== 'permissions' && (
                        <th scope="col" className="px-4 py-3 text-right font-medium">
                            Operasi
                        </th>
                    )}
                </tr>
            </thead>
            <tbody className="divide-y">
                {rows.length === 0 && (
                    <tr>
                        <td
                            colSpan={tab === 'roles' ? 3 + Number(canManage) : 2 + Number(canManage && tab !== 'permissions')}
                            className="text-muted-foreground px-4 py-16 text-center"
                        >
                            <ShieldCheck className="mx-auto mb-3 size-7 text-violet-500" />
                            Tidak ada data akses.
                        </td>
                    </tr>
                )}
                {rows.map((row) => {
                    const role: RoleRow = { ...row, permissions: row.permissions ?? [], users_count: row.users_count ?? 0 };
                    const protectedRole = reservedRole(row.name);
                    const editableRole = !protectedRole && role.permissions.every(canGrant);
                    const editableUser = isSuperSystem || !row.roles?.includes('super-system');
                    return (
                        <tr key={row.id} className="hover:bg-muted/30">
                            <td className="px-4 py-3">
                                <div className="flex min-w-0 items-center gap-2">
                                    <span className={tab === 'users' ? 'font-medium break-words' : 'font-mono text-xs break-all'}>{row.name}</span>
                                    {tab === 'roles' && protectedRole && (
                                        <LockKeyhole aria-label="Role sistem" className="size-4 shrink-0 text-amber-600 dark:text-amber-300" />
                                    )}
                                </div>
                                {tab === 'users' && <p className="text-muted-foreground mt-1 text-xs break-all">{row.email}</p>}
                                {tab === 'users' && Boolean(row.direct_permissions_count) && (
                                    <p className="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                        {row.direct_permissions_count} permission langsung
                                    </p>
                                )}
                            </td>
                            <td className="px-4 py-3">
                                {tab === 'users' ? (
                                    <div className="flex flex-wrap gap-1.5">
                                        {row.roles?.length ? (
                                            row.roles.map((name) => (
                                                <Badge
                                                    key={name}
                                                    variant="outline"
                                                    className="border-violet-500/20 bg-violet-500/10 text-violet-700 dark:text-violet-300"
                                                >
                                                    {name}
                                                </Badge>
                                            ))
                                        ) : (
                                            <span className="text-muted-foreground">Tanpa role</span>
                                        )}
                                    </div>
                                ) : (
                                    <Badge variant="outline" className="border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300">
                                        {tab === 'roles' ? (protectedRole ? 'Sistem' : role.permissions.length) : row.roles_count}
                                    </Badge>
                                )}
                            </td>
                            {tab === 'roles' && <td className="px-4 py-3 tabular-nums">{role.users_count}</td>}
                            {canManage && tab !== 'permissions' && (
                                <td className="px-3 py-1">
                                    <div className="flex justify-end gap-1">
                                        {tab === 'roles' && editableRole && (
                                            <>
                                                {operation(
                                                    `Edit role ${row.name}`,
                                                    <Pencil className="size-4 text-sky-600 dark:text-sky-300" />,
                                                    () => onEdit({ kind: 'role', record: role }),
                                                )}
                                                {operation(
                                                    `Permission ${row.name}`,
                                                    <KeyRound className="size-4 text-violet-600 dark:text-violet-300" />,
                                                    () => onEdit({ kind: 'permissions', record: role }),
                                                )}
                                                {operation(
                                                    `Hapus role ${row.name}`,
                                                    <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />,
                                                    () => onDelete(role),
                                                )}
                                            </>
                                        )}
                                        {tab === 'users' &&
                                            editableUser &&
                                            operation(
                                                `Atur role ${row.name}`,
                                                <UserCog className="size-4 text-emerald-600 dark:text-emerald-300" />,
                                                () => onEdit({ kind: 'user', record: { ...row, email: row.email ?? '', roles: row.roles ?? [] } }),
                                            )}
                                    </div>
                                </td>
                            )}
                        </tr>
                    );
                })}
            </tbody>
        </table>
    );
}
