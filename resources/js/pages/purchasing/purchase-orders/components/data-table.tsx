import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { formatDecimalInput } from '@/lib/decimal-input';
import { Banknote, CheckCheck, Eye, ReceiptText } from 'lucide-react';
import type { PurchaseRow } from '../types';

export function DataTable({
    rows,
    canManage,
    canPost,
    onOpen,
    onLifecycle,
    canPay,
    onPay,
}: {
    rows: PurchaseRow[];
    canManage: boolean;
    canPost: boolean;
    canPay: boolean;
    onPay: (row: PurchaseRow) => void;
    onOpen: (row: PurchaseRow, readOnly: boolean) => void;
    onLifecycle: (row: PurchaseRow) => void;
}) {
    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[920px] text-sm">
                <thead className="bg-muted/40 text-muted-foreground border-b">
                    <tr>
                        {['Nomor', 'Supplier', 'Gudang', 'Baris', 'Total', 'Status', 'Pembayaran', 'Operasi'].map((name) => (
                            <th scope="col" key={name} className="px-4 py-3 text-left font-medium">
                                {name}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={8} className="text-muted-foreground px-4 py-16 text-center">
                                <ReceiptText className="mx-auto mb-3 size-7 text-orange-600 dark:text-orange-300" />
                                Tidak ada pembelian.
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr key={row.id} className="hover:bg-muted/30">
                                <td className="max-w-64 px-4 py-3 font-mono text-xs break-all">{row.number}</td>
                                <td className="max-w-52 px-4 py-3 break-words">{row.supplier_name}</td>
                                <td className="max-w-44 px-4 py-3 break-words">{row.warehouse_name}</td>
                                <td className="px-4 py-3">{row.lines.length}</td>
                                <td className="px-4 py-3 text-right whitespace-nowrap">Rp {formatDecimalInput(row.total_value)}</td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant="outline"
                                        className={
                                            row.status === 'posted'
                                                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                        }
                                    >
                                        {row.status === 'draft' ? 'Draft' : 'Diterima'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant="outline"
                                        className={
                                            row.payment_disbursement_id
                                                ? 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                                : 'text-muted-foreground border-zinc-500/20 bg-zinc-500/5'
                                        }
                                    >
                                        {row.status === 'draft' ? '-' : row.payment_disbursement_id ? 'Lunas' : 'Belum dicatat'}
                                    </Badge>
                                </td>
                                <td className="px-3 py-1">
                                    <div className="flex justify-end gap-1">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Detail ${row.number}`}
                                                    onClick={() => onOpen(row, true)}
                                                >
                                                    <Eye className="size-4 text-violet-600 dark:text-violet-300" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Detail</TooltipContent>
                                        </Tooltip>
                                        {canPost && canPay && row.status === 'draft' && !row.deleted_at && (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Terima dan bayar ${row.number}`}
                                                        onClick={() => onPay(row)}
                                                    >
                                                        <CheckCheck className="size-4 text-emerald-600 dark:text-emerald-300" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Terima dan bayar lunas</TooltipContent>
                                            </Tooltip>
                                        )}
                                        {canPay && row.status === 'posted' && !row.payment_disbursement_id && !row.deleted_at && (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="icon" aria-label={`Bayar ${row.number}`} onClick={() => onPay(row)}>
                                                        <Banknote className="size-4 text-sky-600 dark:text-sky-300" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Bayar pembelian</TooltipContent>
                                            </Tooltip>
                                        )}
                                        {canManage && row.status === 'draft' && (
                                            <MasterActions
                                                record={{ id: row.id, name: row.number, is_active: true, deleted_at: row.deleted_at }}
                                                onEdit={() => onOpen(row, false)}
                                                onLifecycle={() => onLifecycle(row)}
                                            />
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
