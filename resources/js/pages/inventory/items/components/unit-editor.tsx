import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Plus, Trash2 } from 'lucide-react';
import { baseRow, type ItemUnitInput, type UnitOption } from '../types';

export function UnitEditor({
    units,
    options,
    baseUnitId,
    errors,
    disabled,
    onChange,
}: {
    units: ItemUnitInput[];
    options: UnitOption[];
    baseUnitId: string;
    errors: Record<string, string>;
    disabled: boolean;
    onChange: (units: ItemUnitInput[]) => void;
}) {
    const update = (index: number, patch: Partial<ItemUnitInput>) =>
        onChange(units.map((unit, row) => (row === index ? { ...unit, ...patch } : unit)));
    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold">Satuan & barcode</h3>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={disabled || !baseUnitId || units.length >= 100 || units.length >= options.length}
                    onClick={() => onChange([...units, baseRow('')])}
                >
                    <Plus className="size-4 text-emerald-600 dark:text-emerald-300" />
                    Tambah satuan
                </Button>
            </div>
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/40 text-muted-foreground">
                        <tr>
                            <th scope="col" className="px-3 py-2 text-left font-medium">
                                Satuan
                            </th>
                            <th scope="col" className="px-3 py-2 text-left font-medium">
                                Isi satuan dasar
                            </th>
                            <th scope="col" className="px-3 py-2 text-left font-medium">
                                Barcode
                            </th>
                            <th scope="col" className="px-3 py-2 font-medium">
                                Jual
                            </th>
                            <th scope="col" className="px-3 py-2 font-medium">
                                Beli
                            </th>
                            <th scope="col" className="w-12 px-3 py-2">
                                <span className="sr-only">Operasi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {units.map((unit, index) => {
                            const base = unit.unit_id === baseUnitId;
                            return (
                                <tr key={`${index}-${unit.unit_id}`}>
                                    <td className="min-w-36 px-3 py-3 align-top">
                                        <Select
                                            value={unit.unit_id}
                                            onValueChange={(unit_id) => update(index, { unit_id })}
                                            disabled={disabled || base}
                                        >
                                            <SelectTrigger aria-label={`Satuan baris ${index + 1}`}>
                                                <SelectValue placeholder="Pilih satuan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options
                                                    .filter((option) => option.id === unit.unit_id || !units.some((row) => row.unit_id === option.id))
                                                    .map((option) => (
                                                        <SelectItem key={option.id} value={option.id}>
                                                            {option.name} ({option.symbol})
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors[`units.${index}.unit_id`]} />
                                    </td>
                                    <td className="min-w-32 px-3 py-3 align-top">
                                        <Input
                                            aria-label={`Rasio baris ${index + 1}`}
                                            inputMode="decimal"
                                            value={unit.conversion_ratio}
                                            readOnly={base}
                                            disabled={disabled}
                                            maxLength={25}
                                            onChange={(event) => update(index, { conversion_ratio: event.target.value })}
                                            required
                                            aria-invalid={Boolean(errors[`units.${index}.conversion_ratio`])}
                                        />
                                        <InputError message={errors[`units.${index}.conversion_ratio`]} />
                                    </td>
                                    <td className="min-w-40 px-3 py-3 align-top">
                                        <Input
                                            aria-label={`Barcode baris ${index + 1}`}
                                            value={unit.barcode ?? ''}
                                            disabled={disabled}
                                            maxLength={64}
                                            onChange={(event) => update(index, { barcode: event.target.value })}
                                            aria-invalid={Boolean(errors[`units.${index}.barcode`])}
                                        />
                                        <InputError message={errors[`units.${index}.barcode`]} />
                                    </td>
                                    <td className="px-3 py-5 text-center align-top">
                                        <Checkbox
                                            aria-label={`Satuan jual baris ${index + 1}`}
                                            checked={unit.is_sale_unit}
                                            disabled={disabled}
                                            onCheckedChange={(checked) => update(index, { is_sale_unit: checked === true })}
                                        />
                                    </td>
                                    <td className="px-3 py-5 text-center align-top">
                                        <Checkbox
                                            aria-label={`Satuan beli baris ${index + 1}`}
                                            checked={unit.is_purchase_unit}
                                            disabled={disabled}
                                            onCheckedChange={(checked) => update(index, { is_purchase_unit: checked === true })}
                                        />
                                    </td>
                                    <td className="px-2 py-3 align-top">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Hapus satuan baris ${index + 1}`}
                                                    disabled={disabled || base}
                                                    onClick={() => onChange(units.filter((_, row) => row !== index))}
                                                >
                                                    <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>{base ? 'Satuan dasar' : 'Hapus satuan'}</TooltipContent>
                                        </Tooltip>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <InputError message={errors.units} />
        </div>
    );
}
