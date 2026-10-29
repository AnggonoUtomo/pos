import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { Ruler } from 'lucide-react';
import type { UnitRow } from '../types';

export function DataTable({
    rows,
    canManage,
    onEdit,
    onLifecycle,
}: {
    rows: UnitRow[];
    canManage: boolean;
    onEdit: (row: UnitRow) => void;
    onLifecycle: (row: UnitRow) => void;
}) {
    return (
        <table className="workspace-table w-full text-sm">
            <thead className="bg-muted/40 text-muted-foreground border-b">
                <tr>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Kode
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Nama satuan
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Simbol
                    </th>
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
                        <td colSpan={4 + Number(canManage)} className="text-muted-foreground px-4 py-16 text-center">
                            <Ruler className="mx-auto mb-3 size-7 text-amber-600 dark:text-amber-300" />
                            Tidak ada data satuan.
                        </td>
                    </tr>
                ) : (
                    rows.map((row) => (
                        <tr key={row.id} className="hover:bg-muted/30">
                            <td className="px-4 py-3 font-mono text-xs whitespace-nowrap">{row.code}</td>
                            <td className="max-w-sm px-4 py-3 font-medium break-words">{row.name}</td>
                            <td className="px-4 py-3">{row.symbol}</td>
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
