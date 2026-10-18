import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Eye, RotateCcw } from 'lucide-react';
import type { ReturnRow } from '../types';

export function DataTable({
    rows,
    canManage,
    onOpen,
    onLifecycle,
}: {
    rows: ReturnRow[];
    canManage: boolean;
    onOpen: (row: ReturnRow, readOnly: boolean) => void;
    onLifecycle: (row: ReturnRow) => void;
}) {
    return (
        <div className="min-w-0 overflow-x-auto rounded-md border">
            <table className="w-full min-w-[800px] text-sm">
                <thead className="text-muted-foreground bg-muted/40 border-b">
                    <tr>
                        {['Nomor / Invoice', 'Pelanggan', 'Gudang', 'Baris', 'Status', 'Operasi'].map((title) => (
                            <th scope="col" key={title} className="px-4 py-3 text-left font-medium">
                                {title}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={6} className="text-muted-foreground px-4 py-16 text-center">
                                <RotateCcw className="mx-auto mb-3 size-7 text-rose-600 dark:text-rose-300" />
                                Tidak ada draft retur.
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr key={row.id} className="hover:bg-muted/30">
                                <td className="max-w-64 px-4 py-3 font-mono text-xs break-all">
                                    <span className="block">{row.number}</span>
                                    <span className="text-muted-foreground">{row.invoice_number}</span>
                                </td>
                                <td className="max-w-48 px-4 py-3 break-words">{row.customer_name}</td>
                                <td className="max-w-44 px-4 py-3 break-words">{row.snapshot.warehouse_name}</td>
                                <td className="px-4 py-3">{row.lines.length}</td>
                                <td className="px-4 py-3">
                                    <Badge variant="outline" className="border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300">
                                        {row.deleted_at ? 'Terhapus' : 'Draft'}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">
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
