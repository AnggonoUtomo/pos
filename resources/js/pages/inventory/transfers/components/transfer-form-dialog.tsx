import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatDecimalInput as format, parseDecimalInput as parse } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, Save, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import { previewBase } from '../lib/quantity-preview';
import type { TransferOptions, TransferRow } from '../types';

const empty = () => ({ item_id: '', unit_id: '', quantity: '' });
export function TransferFormDialog({
    record,
    options,
    readOnly,
    onClose,
}: {
    record: TransferRow | null;
    options: TransferOptions;
    readOnly: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        source_warehouse_id: record?.source_warehouse_id ?? '',
        destination_warehouse_id: record?.destination_warehouse_id ?? '',
        version: record?.version ?? 1,
        notes: record?.notes ?? '',
        lines: record?.lines.map((line) => ({ item_id: line.item_id, unit_id: line.unit_id, quantity: format(line.quantity) })) ?? [empty()],
    });
    const busy = useRef(false);
    const disabled = readOnly || form.processing;
    const errors = form.errors as Record<string, string>;
    const change = (index: number, patch: Partial<ReturnType<typeof empty>>) =>
        form.setData((data) => ({ ...data, lines: data.lines.map((line, i) => (i === index ? { ...line, ...patch } : line)) }));
    const submit = () => {
        if (disabled || busy.current) return;
        form.clearErrors();
        let invalid = false;
        form.data.lines.forEach((line, i) => {
            if (parse(line.quantity) === null) {
                form.setError(`lines.${i}.quantity` as keyof typeof form.data, 'Format angka belum valid.');
                invalid = true;
            }
        });
        if (invalid) {
            toast.error('Format kuantitas belum valid.');
            return;
        }
        busy.current = true;
        form.transform((data) => ({ ...data, lines: data.lines.map((line) => ({ ...line, quantity: parse(line.quantity)! })) }));
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Draft transfer berhasil disimpan.');
                onClose();
            },
            onError: () => toast.error('Draft belum tersimpan. Periksa isian dan revisi.'),
            onFinish: () => {
                busy.current = false;
            },
        };
        if (record) form.put(route('inventory.transfers.update', { id: record.id }), config);
        else form.post(route('inventory.transfers.store'), config);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-4xl"
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && !readOnly) {
                        e.preventDefault();
                        submit();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>{readOnly ? 'Detail draft transfer' : record ? 'Edit draft transfer' : 'Tambah draft transfer'}</DialogTitle>
                    <DialogDescription className={record ? 'break-all' : 'sr-only'}>
                        {record ? `${record.number} / Revisi ${record.version}` : 'Form draft transfer antar-gudang'}
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="min-w-0 space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                >
                    <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                        {(['source', 'destination'] as const).map((prefix) => {
                            const key = `${prefix}_warehouse_id` as const;
                            const other = prefix === 'source' ? 'destination_warehouse_id' : 'source_warehouse_id';
                            const label = prefix === 'source' ? 'Gudang asal' : 'Gudang tujuan';
                            return (
                                <div key={prefix} className="min-w-0 space-y-2">
                                    <Label htmlFor={`transfer-${prefix}`}>{label}</Label>
                                    {readOnly ? (
                                        <Input id={`transfer-${prefix}`} value={record?.[`${prefix}_warehouse_name`] ?? ''} readOnly />
                                    ) : (
                                        <Select
                                            value={form.data[key]}
                                            disabled={disabled}
                                            onValueChange={(id) =>
                                                form.setData((data) => ({ ...data, [key]: id, [other]: data[other] === id ? '' : data[other] }))
                                            }
                                        >
                                            <SelectTrigger id={`transfer-${prefix}`} className="w-full">
                                                <SelectValue placeholder={`Pilih ${label.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {record && form.data[key] && !options.warehouses.some((row) => row.id === form.data[key]) && (
                                                    <SelectItem value={form.data[key]} disabled>
                                                        {record[`${prefix}_warehouse_name`]} (tidak tersedia)
                                                    </SelectItem>
                                                )}
                                                {options.warehouses.map((row) => (
                                                    <SelectItem key={row.id} value={row.id} disabled={row.id === form.data[other]}>
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
                            <Label htmlFor="transfer-notes">Catatan</Label>
                            <Input
                                id="transfer-notes"
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
                            <table className="w-full min-w-[600px] text-sm">
                                <thead className="bg-muted/40">
                                    <tr>
                                        {['Barang', 'Satuan', 'Kuantitas', 'Kuantitas dasar'].map((label) => (
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
                                                <div>{line.item_name}</div>
                                            </td>
                                            <td className="p-3">{line.unit_name}</td>
                                            <td className="p-3">{format(line.quantity)}</td>
                                            <td className="p-3">
                                                {format(line.base_quantity)} {line.base_unit_name}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="divide-y border-y">
                            {form.data.lines.map((line, index) => {
                                const item = options.items.find((row) => row.id === line.item_id);
                                const original = record?.lines.find((row) => row.item_id === line.item_id);
                                const unit = item?.units.find((row) => row.unit_id === line.unit_id);
                                const base = unit ? previewBase(parse(line.quantity) ?? '', unit.conversion_ratio) : null;
                                return (
                                    <div key={index} className="space-y-2 py-4">
                                        <div className="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_2.25rem]">
                                            <div className="min-w-0 space-y-2">
                                                <Label htmlFor={`transfer-item-${index}`}>Barang {index + 1}</Label>
                                                <Select
                                                    value={line.item_id}
                                                    disabled={disabled}
                                                    onValueChange={(id) =>
                                                        change(index, {
                                                            item_id: id,
                                                            unit_id: options.items.find((row) => row.id === id)?.units[0]?.unit_id ?? '',
                                                        })
                                                    }
                                                >
                                                    <SelectTrigger id={`transfer-item-${index}`} className="w-full">
                                                        <SelectValue placeholder="Pilih barang" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {original && !item && (
                                                            <SelectItem value={line.item_id} disabled>
                                                                {original.item_name} (tidak tersedia)
                                                            </SelectItem>
                                                        )}
                                                        {options.items.map((row) => (
                                                            <SelectItem
                                                                key={row.id}
                                                                value={row.id}
                                                                disabled={form.data.lines.some((other, i) => i !== index && other.item_id === row.id)}
                                                            >
                                                                {row.sku} - {row.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors[`lines.${index}.item_id`]} />
                                            </div>
                                            <div className="min-w-0 space-y-2">
                                                <Label htmlFor={`transfer-unit-${index}`}>Satuan {index + 1}</Label>
                                                <Select
                                                    value={line.unit_id}
                                                    disabled={disabled || !item}
                                                    onValueChange={(unit_id) => change(index, { unit_id })}
                                                >
                                                    <SelectTrigger id={`transfer-unit-${index}`} className="w-full">
                                                        <SelectValue placeholder="Pilih satuan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {original && !item?.units.some((row) => row.unit_id === line.unit_id) && (
                                                            <SelectItem value={line.unit_id} disabled>
                                                                {original.unit_name} (tidak tersedia)
                                                            </SelectItem>
                                                        )}
                                                        {item?.units.map((row) => (
                                                            <SelectItem key={row.unit_id} value={row.unit_id}>
                                                                {options.units.find((unit) => unit.id === row.unit_id)?.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors[`lines.${index}.unit_id`]} />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor={`transfer-quantity-${index}`}>Kuantitas {index + 1}</Label>
                                                <Input
                                                    id={`transfer-quantity-${index}`}
                                                    inputMode="decimal"
                                                    maxLength={32}
                                                    required
                                                    value={line.quantity}
                                                    disabled={disabled}
                                                    onChange={(e) => change(index, { quantity: e.target.value })}
                                                />
                                                <InputError message={errors[`lines.${index}.quantity`]} />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="self-end"
                                                aria-label={`Hapus baris ${index + 1}`}
                                                title={`Hapus baris ${index + 1}`}
                                                disabled={disabled || form.data.lines.length === 1}
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
                                        <p className="text-muted-foreground text-sm">
                                            Kuantitas dasar:{' '}
                                            <output aria-label={`Kuantitas dasar ${index + 1}`}>
                                                {base
                                                    ? `${format(base)} ${options.units.find((row) => row.id === item?.base_unit_id)?.name ?? ''}`
                                                    : '-'}
                                            </output>
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                    <InputError message={errors.lines ?? errors.transfer ?? errors.version} />
                    {!readOnly && (
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Tambah baris"
                            aria-label="Tambah baris"
                            disabled={disabled || form.data.lines.length >= 100}
                            onClick={() => form.setData('lines', [...form.data.lines, empty()])}
                        >
                            <Plus className="size-4 text-emerald-600 dark:text-emerald-300" />
                        </Button>
                    )}
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
