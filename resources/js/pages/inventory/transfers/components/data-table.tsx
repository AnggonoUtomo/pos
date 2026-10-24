import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Eye, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import type { TransferRow } from '../types';

export function TransferDataTable({
    rows,
    canManage,
    onOpen,
    onLifecycle,
}: {
    rows: TransferRow[];
    canManage: boolean;
    onOpen: (row: TransferRow, readOnly: boolean) => void;
    onLifecycle: (row: TransferRow) => void;
}) {
    if (!rows.length)
        return (
            <div className="rounded-md border border-dashed px-4 py-12 text-center">
                <h2 className="font-medium">Belum ada draft transfer</h2>
                <p className="text-muted-foreground mt-1 text-sm">Tidak ada data yang cocok dengan filter.</p>
            </div>
        );
    return (
        <div className="min-w-0 overflow-x-auto rounded-md border">
            <table className="w-full min-w-[900px] text-sm">
                <thead className="bg-muted/40">
                    <tr>
                        {['Nomor transfer', 'Gudang asal', 'Gudang tujuan', 'Barang', 'Revisi', 'Status', 'Aksi'].map((label) => (
                            <th key={label} className="p-3 text-left font-medium">
                                {label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.id} className="border-t align-top">
                            <td className="p-3">
                                <strong className="font-mono text-xs">{row.number}</strong>
                                <div className="text-muted-foreground mt-1 text-xs">{new Date(row.created_at).toLocaleString('id-ID')}</div>
                            </td>
                            <td className="max-w-56 p-3 break-words">
                                {row.source_warehouse_name}
                                <div className="text-muted-foreground text-xs">{row.source_warehouse_code}</div>
                            </td>
                            <td className="max-w-56 p-3 break-words">
                                {row.destination_warehouse_name}
                                <div className="text-muted-foreground text-xs">{row.destination_warehouse_code}</div>
                            </td>
                            <td className="p-3">{row.lines.length} baris</td>
                            <td className="p-3">{row.version}</td>
                            <td className="p-3">
                                <Badge variant={row.deleted_at ? 'outline' : 'secondary'}>
                                    {row.deleted_at ? 'Terhapus' : row.status === 'draft' ? 'Draft' : row.status}
                                </Badge>
                            </td>
                            <td className="p-3">
                                <div className="flex gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label={`Detail ${row.number}`}
                                        title="Detail"
                                        onClick={() => onOpen(row, true)}
                                    >
                                        <Eye className="size-4 text-sky-600 dark:text-sky-300" />
                                    </Button>
                                    {canManage && row.status === 'draft' && !row.deleted_at && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={`Edit ${row.number}`}
                                            title="Edit draft"
                                            onClick={() => onOpen(row, false)}
                                        >
                                            <Pencil className="size-4 text-amber-600 dark:text-amber-300" />
                                        </Button>
                                    )}
                                    {canManage && row.status === 'draft' && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={`${row.deleted_at ? 'Pulihkan' : 'Hapus'} ${row.number}`}
                                            title={row.deleted_at ? 'Pulihkan draft' : 'Hapus draft'}
                                            onClick={() => onLifecycle(row)}
                                        >
                                            {row.deleted_at ? (
                                                <RotateCcw className="size-4 text-emerald-600 dark:text-emerald-300" />
                                            ) : (
                                                <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                            )}
                                        </Button>
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
