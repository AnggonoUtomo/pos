import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, Save, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { decimal, type StockOptions, type StockRow } from '../types';

const emptyLine = () => ({ item_id: '', unit_id: '', quantity: '', unit_cost: '' });
export function OpeningDialog({
    record,
    options,
    readOnly,
    onClose,
}: {
    record: StockRow | null;
    options: StockOptions;
    readOnly: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        warehouse_id: record?.warehouse_id ?? '',
        notes: record?.notes ?? '',
        lines: record?.lines?.map(({ item_id, unit_id, quantity, unit_cost }) => ({ item_id, unit_id, quantity, unit_cost })) ?? [emptyLine()],
    });
    const errors = form.errors as Record<string, string>;
    const disabled = readOnly || form.processing;
    const change = (index: number, patch: Partial<ReturnType<typeof emptyLine>>) =>
        form.setData((current) => ({ ...current, lines: current.lines.map((row, i) => (i === index ? { ...row, ...patch } : row)) }));
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[90dvh] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-5xl"
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{readOnly ? 'Detail stok awal' : record ? 'Edit stok awal' : 'Tambah stok awal'}</DialogTitle>
                    <DialogDescription className="sr-only">Dokumen stok awal per gudang</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (disabled) return;
                        const config = {
                            preserveScroll: true,
                            onSuccess: () => {
                                toast.success('Draft stok awal tersimpan.');
                                onClose();
                            },
                            onError: () => toast.error('Draft belum tersimpan. Periksa isian.'),
                        };
                        if (record) form.put(route('inventory.stock.openings.update', { id: record.id }), config);
                        else form.post(route('inventory.stock.openings.store'), config);
                    }}
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !disabled) {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}
                >
                    {readOnly && <div className="text-sm font-medium break-all">{record?.number}</div>}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="stock-warehouse">Gudang</Label>
                            {readOnly ? (
                                <Input id="stock-warehouse" value={record?.warehouse_name ?? ''} readOnly />
                            ) : (
                                <Select
                                    value={form.data.warehouse_id}
                                    onValueChange={(value) => form.setData('warehouse_id', value)}
                                    disabled={disabled}
                                >
                                    <SelectTrigger id="stock-warehouse" className="w-full" aria-invalid={!!errors.warehouse_id}>
                                        <SelectValue placeholder="Pilih gudang" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.warehouses.map((row) => (
                                            <SelectItem key={row.id} value={row.id}>
                                                {row.code} - {row.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                            <InputError message={errors.warehouse_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="stock-notes">Catatan</Label>
                            <Input
                                id="stock-notes"
                                value={form.data.notes}
                                onChange={(event) => form.setData('notes', event.target.value)}
                                maxLength={500}
                                disabled={disabled}
                            />
                            <InputError message={errors.notes} />
                        </div>
                    </div>
                    {readOnly ? (
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[650px] text-sm">
                                <thead className="bg-muted/40">
                                    <tr>
                                        {['Barang', 'Satuan', 'Kuantitas', 'Harga pokok', 'Stok dasar', 'Nilai stok'].map((text) => (
                                            <th key={text} className="p-3 text-left font-medium">
                                                {text}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {record?.lines?.map((row) => (
                                        <tr key={row.id} className="border-t">
                                            <td className="p-3">
                                                {row.sku}
                                                <div className="text-muted-foreground">{row.item_name}</div>
                                            </td>
                                            <td className="p-3">{row.unit_name}</td>
                                            <td className="p-3 whitespace-nowrap">{decimal(row.quantity)}</td>
                                            <td className="p-3 whitespace-nowrap">Rp {decimal(row.unit_cost)}</td>
                                            <td className="p-3 whitespace-nowrap">
                                                {decimal(row.base_quantity)} {row.base_unit_name}
                                            </td>
                                            <td className="p-3 whitespace-nowrap">Rp {decimal(row.total_value)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="divide-y border-y">
                            {form.data.lines.map((line, index) => {
                                const item = options.items.find((row) => row.id === line.item_id);
                                const units = item?.units.filter((unit) => options.units.some((row) => row.id === unit.unit_id)) ?? [];
                                return (
                                    <div key={index} className="grid gap-3 py-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                                        <div className="min-w-0 space-y-2">
                                            <Label htmlFor={`stock-item-${index}`}>Barang {index + 1}</Label>
                                            <Select
                                                value={line.item_id}
                                                onValueChange={(item_id) => {
                                                    if (!item_id) return;
                                                    const next = options.items.find((row) => row.id === item_id);
                                                    change(index, { item_id, unit_id: next?.base_unit_id ?? '' });
                                                }}
                                                disabled={disabled}
                                            >
                                                <SelectTrigger id={`stock-item-${index}`} className="w-full">
                                                    <SelectValue placeholder="Pilih barang" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options.items
                                                        .filter(
                                                            (row) =>
                                                                row.id === line.item_id ||
                                                                !form.data.lines.some((selected) => selected.item_id === row.id),
                                                        )
                                                        .map((row) => (
                                                            <SelectItem key={row.id} value={row.id}>
                                                                {row.sku} - {row.name}
                                                            </SelectItem>
                                                        ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors[`lines.${index}.item_id`]} />
                                        </div>
                                        <div className="min-w-0 space-y-2">
                                            <Label htmlFor={`stock-unit-${index}`}>Satuan {index + 1}</Label>
                                            <Select
                                                value={line.unit_id}
                                                onValueChange={(unit_id) => {
                                                    if (unit_id) change(index, { unit_id });
                                                }}
                                                disabled={disabled || !item}
                                            >
                                                <SelectTrigger id={`stock-unit-${index}`} className="w-full">
                                                    <SelectValue placeholder="Pilih satuan" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {units.map((unit) => (
                                                        <SelectItem key={unit.unit_id} value={unit.unit_id}>
                                                            {options.units.find((row) => row.id === unit.unit_id)?.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors[`lines.${index}.unit_id`]} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor={`stock-quantity-${index}`}>Kuantitas {index + 1}</Label>
                                            <Input
                                                id={`stock-quantity-${index}`}
                                                inputMode="decimal"
                                                value={line.quantity}
                                                onChange={(event) => change(index, { quantity: event.target.value })}
                                                maxLength={21}
                                                required
                                                disabled={disabled}
                                            />
                                            <InputError message={errors[`lines.${index}.quantity`]} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor={`stock-cost-${index}`}>Harga pokok {index + 1}</Label>
                                            <Input
                                                id={`stock-cost-${index}`}
                                                inputMode="decimal"
                                                value={line.unit_cost}
                                                onChange={(event) => change(index, { unit_cost: event.target.value })}
                                                maxLength={21}
                                                required
                                                disabled={disabled}
                                            />
                                            <InputError message={errors[`lines.${index}.unit_cost`]} />
                                        </div>
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            className="self-end"
                                            title={`Hapus baris ${index + 1}`}
                                            aria-label={`Hapus baris ${index + 1}`}
                                            disabled={disabled || form.data.lines.length <= 1}
                                            onClick={() =>
                                                form.setData(
                                                    'lines',
                                                    form.data.lines.filter((_, i) => i !== index),
                                                )
                                            }
                                        >
                                            <Trash2 className="size-4 text-rose-600" />
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                    <InputError message={errors.lines || errors.stock} />
                    {!readOnly && (
                        <Button
                            type="button"
                            variant="outline"
                            disabled={disabled || form.data.lines.length >= 100}
                            onClick={() => form.setData('lines', [...form.data.lines, emptyLine()])}
                        >
                            <Plus className="size-4 text-emerald-600" />
                            Tambah baris
                        </Button>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose} disabled={form.processing}>
                            {readOnly ? 'Tutup' : 'Batal'}
                        </Button>
                        {!readOnly && (
                            <Button type="submit" disabled={disabled}>
                                {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan draft
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
