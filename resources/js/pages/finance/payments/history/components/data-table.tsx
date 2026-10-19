import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { formatDecimalInput as format } from '@/lib/decimal-input';
import { ArrowDownLeft, ArrowUpRight, Eye, ReceiptText } from 'lucide-react';
import { dateLabel, directionLabel, sourceLabel, type PaymentRow } from '../types';

export function DataTable({ rows, onOpen, disabled }: { rows: PaymentRow[]; onOpen: (row: PaymentRow) => void; disabled: boolean }) {
    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[860px] text-sm">
                <thead className="bg-muted/40 text-muted-foreground border-b">
                    <tr>
                        {['Waktu (WIB)', 'Nomor transaksi', 'Arah', 'Total dokumen', 'Detail'].map((label) => (
                            <th key={label} scope="col" className="px-4 py-3 text-left font-medium">
                                {label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={5} className="text-muted-foreground px-4 py-16 text-center">
                                <ReceiptText className="mx-auto mb-3 size-7 text-sky-600 dark:text-sky-300" />
                                Tidak ada pembayaran.
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr key={`${row.source_type}-${row.id}`} className="hover:bg-muted/30">
                                <td className="px-4 py-3 whitespace-nowrap">{dateLabel(row.occurred_at)}</td>
                                <td className="max-w-64 px-4 py-3 break-all">
                                    <span className="block font-mono text-xs">{row.source_number}</span>
                                    <span className="text-muted-foreground text-xs">{sourceLabel(row.source_type)}</span>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant="outline"
                                        className={
                                            row.direction === 'incoming'
                                                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                : 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300'
                                        }
                                    >
                                        {row.direction === 'incoming' ? <ArrowDownLeft className="size-3" /> : <ArrowUpRight className="size-3" />}
                                        {directionLabel(row.direction)}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3 text-right whitespace-nowrap">Rp {format(row.total)}</td>
                                <td className="px-4 py-2">
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                disabled={disabled}
                                                aria-label={`Detail ${row.source_number}`}
                                                onClick={() => onOpen(row)}
                                            >
                                                <Eye className="size-4 text-violet-600 dark:text-violet-300" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Detail pembayaran</TooltipContent>
                                    </Tooltip>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
