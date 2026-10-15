import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { CheckCheck, Eye, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { decimal, type StockRow, type StockTab } from '../types';

function Tool({ label, children, onClick }: { label: string; children: ReactNode; onClick: () => void }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button size="icon" variant="ghost" aria-label={label} onClick={onClick}>
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}
export function DataTable({
    rows,
    tab,
    canManage,
    canPost,
    onDetail,
    onEdit,
    onAction,
}: {
    rows: StockRow[];
    tab: StockTab;
    canManage: boolean;
    canPost: boolean;
    onDetail: (row: StockRow) => void;
    onEdit: (row: StockRow) => void;
    onAction: (record: StockRow, action: 'post' | 'destroy' | 'restore') => void;
}) {
    const headings =
        tab === 'openings'
            ? ['Dokumen', 'Gudang', 'Status', 'Baris', 'Operasi']
            : tab === 'balances'
              ? ['Barang', 'Gudang', 'Saldo dasar', 'Nilai stok']
              : ['Waktu', 'Dokumen', 'Barang', 'Gudang', 'Masuk / keluar', 'Nilai'];
    return (
        <table className="w-full min-w-[650px] text-sm">
            <thead className="bg-muted/40">
                <tr>
                    {headings.map((label) => (
                        <th key={label} className="p-3 text-left font-medium whitespace-nowrap">
                            {label}
                        </th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {rows.length === 0 && (
                    <tr>
                        <td colSpan={headings.length} className="text-muted-foreground p-10 text-center">
                            Tidak ada data stok.
                        </td>
                    </tr>
                )}
                {rows.map((row) => (
                    <tr key={row.id} className="hover:bg-muted/30 border-t">
                        {tab === 'openings' ? (
                            <>
                                <td className="p-3">
                                    <span className="block max-w-64 font-mono text-xs break-all">{row.number}</span>
                                    {row.posted_at && (
                                        <span className="text-muted-foreground text-xs">{new Date(row.posted_at).toLocaleString('id-ID')}</span>
                                    )}
                                </td>
                                <td className="p-3">
                                    <span className="block max-w-52 break-words">{row.warehouse_name}</span>
                                </td>
                                <td className="p-3">
                                    <span
                                        className={`inline-flex rounded px-2 py-1 text-xs font-medium ${row.status === 'posted' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/10 text-amber-700 dark:text-amber-300'}`}
                                    >
                                        {row.deleted_at ? 'Terhapus' : row.status === 'posted' ? 'Posted' : 'Draft'}
                                    </span>
                                </td>
                                <td className="p-3">{row.lines?.length ?? 0}</td>
                                <td className="p-3">
                                    <div className="flex items-center gap-1">
                                        <Tool label={`Detail ${row.number}`} onClick={() => onDetail(row)}>
                                            <Eye className="size-4 text-sky-600 dark:text-sky-300" />
                                        </Tool>
                                        {row.status === 'draft' && !row.deleted_at && (
                                            <>
                                                {canManage && (
                                                    <Tool label={`Edit ${row.number}`} onClick={() => onEdit(row)}>
                                                        <Pencil className="size-4 text-amber-600 dark:text-amber-300" />
                                                    </Tool>
                                                )}
                                                {canPost && (
                                                    <Tool label={`Posting ${row.number}`} onClick={() => onAction(row, 'post')}>
                                                        <CheckCheck className="size-4 text-emerald-600 dark:text-emerald-300" />
                                                    </Tool>
                                                )}
                                                {canManage && (
                                                    <Tool label={`Hapus ${row.number}`} onClick={() => onAction(row, 'destroy')}>
                                                        <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                                    </Tool>
                                                )}
                                            </>
                                        )}
                                        {row.deleted_at && canManage && (
                                            <Tool label={`Pulihkan ${row.number}`} onClick={() => onAction(row, 'restore')}>
                                                <RotateCcw className="size-4 text-violet-600 dark:text-violet-300" />
                                            </Tool>
                                        )}
                                    </div>
                                </td>
                            </>
                        ) : (
                            <>
                                {tab === 'movements' && (
                                    <>
                                        <td className="p-3 whitespace-nowrap">
                                            {row.posted_at ? new Date(row.posted_at).toLocaleString('id-ID') : '-'}
                                        </td>
                                        <td className="p-3 font-mono text-xs">
                                            <span className="block max-w-52 break-all">{row.number}</span>
                                        </td>
                                    </>
                                )}
                                <td className="p-3">
                                    <span className="font-medium">{row.sku}</span>
                                    <span className="text-muted-foreground block max-w-52 break-words">{row.item_name}</span>
                                </td>
                                <td className="p-3">
                                    <span className="block max-w-52 break-words">{row.warehouse_name}</span>
                                </td>
                                <td className="p-3 whitespace-nowrap tabular-nums">
                                    {decimal(row.quantity)} {row.base_unit_name}
                                </td>
                                <td className="p-3 whitespace-nowrap tabular-nums">Rp {decimal(row.total_value)}</td>
                            </>
                        )}
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
