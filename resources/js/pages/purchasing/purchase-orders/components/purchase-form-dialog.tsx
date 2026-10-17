import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatDecimalInput as format, parseDecimalInput as parse } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, Save, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { previewPurchaseLine, sumPurchaseValues } from '../lib/purchase-preview';
import type { PurchaseOptions, PurchaseRow } from '../types';

const empty = () => ({ item_id: '', unit_id: '', quantity: '', unit_cost: '' });
export function PurchaseFormDialog({
    record,
    options,
    readOnly,
    onClose,
}: {
    record: PurchaseRow | null;
    options: PurchaseOptions;
    readOnly: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        supplier_id: record?.supplier_id ?? '',
        warehouse_id: record?.warehouse_id ?? '',
        version: record?.version ?? 1,
        notes: record?.notes ?? '',
        lines: record?.lines.map((line) => ({
            item_id: line.item_id,
            unit_id: line.unit_id,
            quantity: format(line.quantity),
            unit_cost: format(line.unit_cost),
        })) ?? [empty()],
    });
    const errors = form.errors as Record<string, string>;
    const disabled = readOnly || form.processing;
    const change = (index: number, patch: Partial<ReturnType<typeof empty>>) =>
        form.setData((data) => ({ ...data, lines: data.lines.map((line, i) => (i === index ? { ...line, ...patch } : line)) }));
    const previews = form.data.lines.map((line) => {
        const ratio = options.items.find((item) => item.id === line.item_id)?.units.find((unit) => unit.unit_id === line.unit_id)?.conversion_ratio;
        return ratio ? previewPurchaseLine(parse(line.quantity) ?? '', parse(line.unit_cost) ?? '', ratio) : null;
    });
    const total = previews.every(Boolean) ? sumPurchaseValues(previews.map((p) => p!.totalValue)) : null;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (disabled) return;
        form.clearErrors();
        let invalid = false;
        for (const [index, line] of form.data.lines.entries()) {
            for (const key of ['quantity', 'unit_cost'] as const) {
                if (parse(line[key]) === null) {
                    form.setError(`lines.${index}.${key}` as keyof typeof form.data, 'Gunakan angka Indonesia, misalnya 1,5 atau 120.000.');
                    invalid = true;
                }
            }
        }
        if (invalid) {
            toast.error('Format angka belum valid.');
            return;
        }
        form.transform((data) => ({
            ...data,
            lines: data.lines.map((line) => ({ ...line, quantity: parse(line.quantity)!, unit_cost: parse(line.unit_cost)! })),
        }));
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Draft pembelian berhasil disimpan.');
                onClose();
            },
            onError: () => toast.error('Draft belum tersimpan. Periksa isian.'),
        };
        if (record) form.put(route('purchasing.purchase-orders.update', { id: record.id }), config);
        else form.post(route('purchasing.purchase-orders.store'), config);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-6xl"
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>
                        {readOnly
                            ? record?.status === 'posted'
                                ? 'Detail penerimaan pembelian'
                                : 'Detail draft pembelian'
                            : record
                              ? 'Edit draft pembelian'
                              : 'Tambah draft pembelian'}
                    </DialogTitle>
                    <DialogDescription className={record ? 'break-all' : 'sr-only'}>
                        {record ? `${record.number} / Revisi ${record.version}` : 'Form draft pembelian'}
                    </DialogDescription>
                </DialogHeader>
                {record?.posted_at && (
                    <p className="text-muted-foreground min-w-0 text-sm">Diterima {new Date(record.posted_at).toLocaleString('id-ID')}</p>
                )}
                <form
                    onSubmit={submit}
                    className="min-w-0 space-y-4"
                    onKeyDown={(e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && !readOnly) {
                            e.preventDefault();
                            e.currentTarget.requestSubmit();
                        }
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(['supplier', 'warehouse'] as const).map((kind) => {
                            const label = kind === 'supplier' ? 'Supplier' : 'Gudang';
                            const key = `${kind}_id` as const;
                            const choices = kind === 'supplier' ? options.suppliers : options.warehouses;
                            return (
                                <div key={kind} className="min-w-0 space-y-2">
                                    <Label htmlFor={`purchase-${kind}`}>{label}</Label>
                                    {readOnly ? (
                                        <Input id={`purchase-${kind}`} value={record?.[`${kind}_name`] ?? ''} readOnly />
                                    ) : (
                                        <Select value={form.data[key]} onValueChange={(id) => form.setData(key, id)} disabled={disabled}>
                                            <SelectTrigger id={`purchase-${kind}`} className="w-full" aria-invalid={!!errors[key]}>
                                                <SelectValue placeholder={`Pilih ${label.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {record && !choices.some((row) => row.id === form.data[key]) && form.data[key] && (
                                                    <SelectItem value={form.data[key]} disabled>
                                                        {record[`${kind}_name`]} (tidak tersedia)
                                                    </SelectItem>
                                                )}
                                                {choices.map((row) => (
                                                    <SelectItem key={row.id} value={row.id}>
                                                        {row.code} - {row.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                    <InputError message={errors[key]} />
                                </div>
                            );
                        })}
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="purchase-notes">Catatan</Label>
                            <Input
                                id="purchase-notes"
                                value={form.data.notes}
                                maxLength={500}
                                readOnly={readOnly}
                                disabled={form.processing}
                                onChange={(e) => form.setData('notes', e.target.value)}
                            />
                            <InputError message={errors.notes} />
                        </div>
                    </div>
                    {readOnly ? (
                        <div className="min-w-0 overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[760px] text-sm">
                                <thead className="bg-muted/40">
                                    <tr>
                                        {['Barang', 'Satuan', 'Kuantitas', 'Harga beli', 'Kuantitas dasar', 'Subtotal'].map((label) => (
                                            <th key={label} className="p-3 text-left font-medium">
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {record?.lines.map((line) => (
                                        <tr key={line.id} className="border-t">
                                            <td className="max-w-64 p-3 break-words">
                                                {line.sku}
                                                <div className="text-muted-foreground">{line.item_name}</div>
                                            </td>
                                            <td className="p-3">{line.unit_name}</td>
                                            <td className="p-3 whitespace-nowrap">{format(line.quantity)}</td>
                                            <td className="p-3 whitespace-nowrap">Rp {format(line.unit_cost)}</td>
                                            <td className="p-3 whitespace-nowrap">
                                                {format(line.base_quantity)} {line.base_unit_name}
                                            </td>
                                            <td className="p-3 whitespace-nowrap">Rp {format(line.total_value)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="divide-y border-y">
                            {form.data.lines.map((line, index) => {
                                const item = options.items.find((row) => row.id === line.item_id);
                                const units = item?.units ?? [];
                                const original = record?.lines.find((row) => row.item_id === line.item_id);
                                const base = options.units.find((row) => row.id === item?.base_unit_id);
                                return (
                                    <div key={index} className="space-y-2 py-4">
                                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                                            <div className="min-w-0 space-y-2">
                                                <Label htmlFor={`purchase-item-${index}`}>Barang {index + 1}</Label>
                                                <Select
                                                    value={line.item_id}
                                                    disabled={disabled}
                                                    onValueChange={(id) => {
                                                        const next = options.items.find((row) => row.id === id);
                                                        change(index, { item_id: id, unit_id: next?.units[0]?.unit_id ?? '' });
                                                    }}
                                                >
                                                    <SelectTrigger id={`purchase-item-${index}`} className="w-full">
                                                        <SelectValue placeholder="Pilih barang" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {original && !item && (
                                                            <SelectItem value={line.item_id} disabled>
                                                                {original.item_name} (tidak tersedia)
                                                            </SelectItem>
                                                        )}
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
                                                <Label htmlFor={`purchase-unit-${index}`}>Satuan {index + 1}</Label>
                                                <Select
                                                    value={line.unit_id}
                                                    disabled={disabled || !item}
                                                    onValueChange={(unit_id) => change(index, { unit_id })}
                                                >
                                                    <SelectTrigger id={`purchase-unit-${index}`} className="w-full">
                                                        <SelectValue placeholder="Pilih satuan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {original &&
                                                            line.unit_id === original.unit_id &&
                                                            !units.some((unit) => unit.unit_id === line.unit_id) && (
                                                                <SelectItem value={line.unit_id} disabled>
                                                                    {original.unit_name} (tidak tersedia)
                                                                </SelectItem>
                                                            )}
                                                        {units.map((unit) => (
                                                            <SelectItem key={unit.unit_id} value={unit.unit_id}>
                                                                {options.units.find((row) => row.id === unit.unit_id)?.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors[`lines.${index}.unit_id`]} />
                                            </div>
                                            {(['quantity', 'unit_cost'] as const).map((key) => (
                                                <div key={key} className="space-y-2">
                                                    <Label htmlFor={`purchase-${key}-${index}`}>
                                                        {key === 'quantity' ? 'Kuantitas' : 'Harga beli'} {index + 1}
                                                    </Label>
                                                    <Input
                                                        id={`purchase-${key}-${index}`}
                                                        inputMode="decimal"
                                                        required
                                                        maxLength={32}
                                                        value={line[key]}
                                                        disabled={disabled}
                                                        onChange={(e) => change(index, { [key]: e.target.value })}
                                                        aria-invalid={!!errors[`lines.${index}.${key}`]}
                                                    />
                                                    <InputError message={errors[`lines.${index}.${key}`]} />
                                                </div>
                                            ))}
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
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
                                                <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                            </Button>
                                        </div>
                                        <div className="text-muted-foreground flex flex-wrap justify-between gap-2 text-sm">
                                            <span>
                                                Kuantitas dasar:{' '}
                                                {previews[index] ? `${format(previews[index]!.baseQuantity)} ${base?.name ?? ''}` : '-'}
                                            </span>
                                            <span>Subtotal: {previews[index] ? `Rp ${format(previews[index]!.totalValue)}` : '-'}</span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                    <InputError message={errors.lines ?? errors.purchase_order ?? errors.version} />
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        {!readOnly && (
                            <Button
                                type="button"
                                variant="outline"
                                disabled={disabled || form.data.lines.length >= 100}
                                onClick={() => form.setData('lines', [...form.data.lines, empty()])}
                            >
                                <Plus className="size-4 text-emerald-600" />
                                Tambah baris
                            </Button>
                        )}
                        <p className="ml-auto text-right font-semibold" aria-label="Total pembelian">
                            Total: {readOnly && record ? `Rp ${format(record.total_value)}` : total ? `Rp ${format(total)}` : '-'}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
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
