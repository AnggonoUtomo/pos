import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { Truck } from 'lucide-react';
import type { SupplierRow } from '../types';

export function DataTable({
    rows,
    canManage,
    onEdit,
    onLifecycle,
}: {
    rows: SupplierRow[];
    canManage: boolean;
    onEdit: (row: SupplierRow) => void;
    onLifecycle: (row: SupplierRow) => void;
}) {
    return (
        <table className="workspace-table w-full min-w-[760px] text-sm">
            <thead className="bg-muted/40 text-muted-foreground border-b">
                <tr>
                    {['Kode', 'Nama', 'Kontak', 'Alamat', 'Status'].map((label) => (
                        <th scope="col" key={label} className="px-4 py-3 text-left font-medium">
                            {label}
                        </th>
                    ))}
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
                        <td colSpan={5 + Number(canManage)} className="text-muted-foreground px-4 py-16 text-center">
                            <Truck className="mx-auto mb-3 size-7 text-blue-600 dark:text-blue-300" />
                            Tidak ada data.
                        </td>
                    </tr>
                ) : (
                    rows.map((row) => (
                        <tr key={row.id} className="hover:bg-muted/30">
                            <td className="max-w-44 px-4 py-3 font-mono text-xs break-words">{row.code}</td>
                            <td className="max-w-56 px-4 py-3 font-medium break-words">{row.name}</td>
                            <td className="max-w-52 px-4 py-3 break-words">
                                <div>{row.phone ?? '-'}</div>
                                <div className="text-muted-foreground text-xs">{row.email}</div>
                            </td>
                            <td className="max-w-64 px-4 py-3 break-words whitespace-pre-wrap">{row.address ?? '-'}</td>
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
