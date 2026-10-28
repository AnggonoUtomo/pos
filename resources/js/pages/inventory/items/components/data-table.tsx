import { ItemImage } from '@/components/shared/item-image';
import { MasterActions } from '@/components/shared/master-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Package, ScanBarcode } from 'lucide-react';
import type { ItemRow, UnitOption } from '../types';

export function DataTable({
    rows,
    unitOptions,
    canManage,
    onEdit,
    onLifecycle,
    onLabels,
}: {
    rows: ItemRow[];
    unitOptions: UnitOption[];
    canManage: boolean;
    onEdit: (row: ItemRow) => void;
    onLifecycle: (row: ItemRow) => void;
    onLabels: (row: ItemRow) => void;
}) {
    const labels = new Map(unitOptions.map((unit) => [unit.id, unit]));
    return (
        <table className="w-full text-sm">
            <thead className="bg-muted/40 text-muted-foreground border-b">
                <tr>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        SKU
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Nama barang
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Satuan dasar
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Satuan
                    </th>
                    <th scope="col" className="px-4 py-3 text-left font-medium">
                        Status
                    </th>
                    {
                        <th scope="col" className="px-4 py-3 text-right font-medium">
                            Operasi
                        </th>
                    }
                </tr>
            </thead>
            <tbody className="divide-y">
                {rows.length === 0 ? (
                    <tr>
                        <td colSpan={6} className="text-muted-foreground px-4 py-16 text-center">
                            <Package className="mx-auto mb-3 size-7 text-teal-600 dark:text-teal-300" />
                            Tidak ada data barang.
                        </td>
                    </tr>
                ) : (
                    rows.map((row) => (
                        <tr key={row.id} className="hover:bg-muted/30">
                            <td className="max-w-56 px-4 py-3 font-mono text-xs break-all">{row.sku}</td>
                            <td className="max-w-sm min-w-36 px-4 py-3 font-medium break-words">
                                <div className="flex items-center gap-3">
                                    <ItemImage src={row.image_url} name={row.name} />
                                    <span className="min-w-0 break-words">{row.name}</span>
                                </div>
                            </td>
                            <td className="px-4 py-3">{labels.get(row.base_unit_id)?.name ?? row.base_unit_id}</td>
                            <td className="px-4 py-3">
                                <Badge variant="outline" className="border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300">
                                    {row.units.length}
                                </Badge>
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
                            {
                                <td className="px-3 py-1">
                                    <div className="flex items-center justify-end gap-1">
                                        {!row.deleted_at && row.is_active && (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Label barcode ${row.name}`}
                                                        onClick={() => onLabels(row)}
                                                    >
                                                        <ScanBarcode className="text-sky-600 dark:text-sky-300" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Label barcode</TooltipContent>
                                            </Tooltip>
                                        )}
                                        {canManage && <MasterActions record={row} onEdit={() => onEdit(row)} onLifecycle={() => onLifecycle(row)} />}
                                    </div>
                                </td>
                            }
                        </tr>
                    ))
                )}
            </tbody>
        </table>
    );
}
