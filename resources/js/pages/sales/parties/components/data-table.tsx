import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { UsersRound } from 'lucide-react';
import type { ReactNode } from 'react';
import type { LevelRow } from '../types';

export function DataTable<T extends LevelRow>({
    rows,
    canManage,
    onEdit,
    onLifecycle,
    customerDetails,
}: {
    rows: T[];
    canManage: boolean;
    onEdit: (row: T) => void;
    onLifecycle: (row: T) => void;
    customerDetails?: (row: T) => ReactNode;
}) {
    return (
        <table className="w-full text-sm">
            <thead className="bg-muted/40 text-muted-foreground border-b">
                <tr>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Kode
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Nama
                    </th>
                    {customerDetails && (
                        <>
                            <th scope="col" className="px-4 py-3 text-left font-medium">
                                Level
                            </th>
                            <th scope="col" className="px-4 py-3 text-left font-medium">
                                Kontak
                            </th>
                        </>
                    )}
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Status
                    </th>
                    {canManage && (
                        <th scope="col" className="px-4 py-3 text-right font-medium">
                            Operasi
                        </th>
                    )}
                </tr>
            </thead>
            <tbody className="divide-y">
                {rows.length === 0 ? (
                    <tr>
                        <td colSpan={3 + (customerDetails ? 2 : 0) + Number(canManage)} className="text-muted-foreground px-4 py-16 text-center">
                            <UsersRound className="mx-auto mb-3 size-7 text-cyan-600 dark:text-cyan-300" />
                            Tidak ada data.
                        </td>
                    </tr>
                ) : (
                    rows.map((row) => (
                        <tr key={row.id} className="hover:bg-muted/30">
                            <td className="px-4 py-3 font-mono text-xs whitespace-nowrap">{row.code}</td>
                            <td className="max-w-64 px-4 py-3 font-medium break-words">{row.name}</td>
                            {customerDetails?.(row)}
                            <td className="px-4 py-3">
                                <Badge
                                    variant="outline"
                                    className={
                                        row.is_active
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                            : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                    }
                                >
                                    {row.is_active ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            </td>
                            {canManage && (
                                <td className="px-3 py-1">
                                    <MasterActions record={row} onEdit={() => onEdit(row)} onLifecycle={() => onLifecycle(row)} />
                                </td>
                            )}
                        </tr>
                    ))
                )}
            </tbody>
        </table>
    );
}
