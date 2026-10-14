import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { Landmark } from 'lucide-react';
import type { AccountRow, MethodRow } from '../types';
import { accountTypes, methodTypes } from '../types';

export function DataTable<T extends AccountRow | MethodRow>({
    rows,
    methods = false,
    canManage,
    onEdit,
    onLifecycle,
}: {
    rows: T[];
    methods?: boolean;
    canManage: boolean;
    onEdit: (row: T) => void;
    onLifecycle: (row: T) => void;
}) {
    return (
        <table className="w-full min-w-[720px] text-sm">
            <thead className="bg-muted/40 text-muted-foreground border-b">
                <tr>
                    {['Kode', 'Nama', 'Jenis', methods ? 'Akun tujuan' : 'Bank / rekening', 'Status', ...(canManage ? ['Operasi'] : [])].map(
                        (label) => (
                            <th key={label} scope="col" className="px-4 py-3 text-left font-medium">
                                {label}
                            </th>
                        ),
                    )}
                </tr>
            </thead>
            <tbody className="divide-y">
                {rows.length === 0 ? (
                    <tr>
                        <td colSpan={canManage ? 6 : 5} className="text-muted-foreground px-4 py-16 text-center">
                            <Landmark className="mx-auto mb-3 size-7 text-emerald-600 dark:text-emerald-300" />
                            Tidak ada data.
                        </td>
                    </tr>
                ) : (
                    rows.map((row) => (
                        <tr key={row.id} className="hover:bg-muted/30">
                            <td className="max-w-40 min-w-28 px-4 py-3 font-mono text-xs break-all">{row.code}</td>
                            <td className="max-w-64 px-4 py-3 font-medium break-words">{row.name}</td>
                            <td className="px-4 py-3">
                                <Badge
                                    variant="outline"
                                    className={
                                        row.type === 'cash'
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                            : 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                    }
                                >
                                    {'payment_account_id' in row ? methodTypes[row.type] : accountTypes[row.type]}
                                </Badge>
                            </td>
                            <td className="max-w-64 px-4 py-3 break-words">
                                {'payment_account_id' in row ? (
                                    row.account_name
                                ) : (
                                    <>
                                        <div>{row.bank_name ?? '-'}</div>
                                        {row.account_number && (
                                            <div className="text-muted-foreground font-mono text-xs">****{row.account_number.slice(-4)}</div>
                                        )}
                                    </>
                                )}
                            </td>
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
