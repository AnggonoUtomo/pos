import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Banknote, Eye, Pencil, RotateCcw, Trash2, Truck } from 'lucide-react';
import type { ReturnRow } from '../types';

export function ReturnDataTable({
    rows,
    trashed,
    canManage,
    canPost,
    canRefund,
    onRefund,
    onOpen,
    onLifecycle,
    onPost,
}: {
    rows: ReturnRow[];
    trashed: boolean;
    canManage: boolean;
    canPost: boolean;
    canRefund: boolean;
    onRefund: (row: ReturnRow) => void;
    onOpen: (row: ReturnRow, readOnly: boolean) => void;
    onLifecycle: (row: ReturnRow) => void;
    onPost: (row: ReturnRow) => void;
}) {
    if (!rows.length)
        return (
            <div className="rounded-md border border-dashed px-4 py-12 text-center">
                <h2 className="font-medium">Belum ada retur</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    {trashed ? 'Tidak ada draft retur terhapus yang cocok.' : 'Retur akan muncul setelah dibuat dari pembelian yang diterima.'}
                </p>
            </div>
        );
    return (
        <div className="min-w-0 overflow-x-auto rounded-md border">
            <table className="workspace-table w-full min-w-[900px] text-sm">
                <thead className="bg-muted/40 text-left">
                    <tr>
                        {['Nomor retur', 'Pembelian asal', 'Supplier', 'Barang', 'Revisi', 'Status', 'Aksi'].map((title) => (
                            <th key={title} className="p-3 font-medium">
                                {title}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.id} className="border-t align-top">
                            <td className="p-3">
                                <strong>{row.number}</strong>
                                <div className="text-muted-foreground text-xs">{new Date(row.created_at).toLocaleString('id-ID')}</div>
                            </td>
                            <td className="p-3">{row.purchase_order_number}</td>
                            <td className="max-w-52 p-3 break-words">{row.supplier_name}</td>
                            <td className="p-3">{row.lines.length} baris</td>
                            <td className="p-3">{row.version}</td>
                            <td className="p-3">
                                <Badge variant={trashed ? 'outline' : row.status === 'posted' ? 'default' : 'secondary'}>
                                    {trashed ? 'Terhapus' : row.status === 'posted' ? 'Diserahkan' : 'Draft'}
                                </Badge>
                                {row.posted_at && (
                                    <span className="text-muted-foreground mt-1 block text-xs">
                                        {new Date(row.posted_at).toLocaleString('id-ID')}
                                    </span>
                                )}
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
                                    {!trashed && row.status === 'posted' && (row.settlement || (canRefund && row.refund_available)) && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={`${row.settlement ? 'Hasil penerimaan' : 'Terima uang'} ${row.number}`}
                                            title={row.settlement ? 'Hasil penerimaan supplier' : 'Terima uang supplier'}
                                            onClick={() => onRefund(row)}
                                        >
                                            <Banknote className="size-4 text-emerald-600 dark:text-emerald-300" />
                                        </Button>
                                    )}
                                    {canPost && row.status === 'draft' && !trashed && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={`Serahkan ${row.number}`}
                                            title="Posting serah barang"
                                            onClick={() => onPost(row)}
                                        >
                                            <Truck className="size-4 text-emerald-600 dark:text-emerald-300" />
                                        </Button>
                                    )}
                                    {canManage && row.status === 'draft' && !trashed && (
                                        <>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                aria-label={`Edit ${row.number}`}
                                                title="Edit draft"
                                                onClick={() => onOpen(row, false)}
                                            >
                                                <Pencil className="size-4 text-amber-600 dark:text-amber-300" />
                                            </Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                aria-label={`Hapus ${row.number}`}
                                                title="Hapus draft"
                                                onClick={() => onLifecycle(row)}
                                            >
                                                <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                            </Button>
                                        </>
                                    )}
                                    {canManage && row.status === 'draft' && trashed && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            aria-label={`Pulihkan ${row.number}`}
                                            title="Pulihkan draft"
                                            onClick={() => onLifecycle(row)}
                                        >
                                            <RotateCcw className="size-4 text-emerald-600 dark:text-emerald-300" />
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
