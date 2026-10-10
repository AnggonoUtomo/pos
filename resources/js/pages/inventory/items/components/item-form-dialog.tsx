import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { type FormEvent } from 'react';
import { toast } from 'sonner';
import { baseRow, type ItemRow, type ItemUnitInput, type UnitOption } from '../types';
import { UnitEditor } from './unit-editor';

export function ItemFormDialog({ record, options, onClose }: { record: ItemRow | null; options: UnitOption[]; onClose: () => void }) {
    const initialUnits: ItemUnitInput[] =
        record?.units.map((unit) => ({
            unit_id: unit.unit_id,
            conversion_ratio: unit.conversion_ratio,
            barcode: unit.barcode,
            is_sale_unit: unit.is_sale_unit,
            is_purchase_unit: unit.is_purchase_unit,
        })) ?? [];
    const form = useForm({
        sku: record?.sku ?? '',
        name: record?.name ?? '',
        base_unit_id: record?.base_unit_id ?? '',
        is_active: record?.is_active ?? true,
        units: initialUnits,
    });
    const errors = form.errors as Record<string, string>;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Barang berhasil diperbarui.' : 'Barang berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Barang belum tersimpan. Periksa isian form.'),
        };
        if (record) form.put(route('inventory.items.update', { id: record.id }), config);
        else form.post(route('inventory.items.store'), config);
    };
    const chooseBase = (id: string) => {
        const selected = form.data.units.find((unit) => unit.unit_id === id) ?? baseRow(id);
        form.setData({
            ...form.data,
            base_unit_id: id,
            units: [
                { ...selected, conversion_ratio: '1' },
                ...form.data.units.filter((unit) => unit.unit_id !== form.data.base_unit_id && unit.unit_id !== id),
            ],
        });
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[90dvh] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-4xl"
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{record ? 'Edit' : 'Tambah'} barang</DialogTitle>
                    <DialogDescription className="sr-only">Form barang dan satuan</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-5"
                    onSubmit={submit}
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="item-sku">SKU</Label>
                            <Input
                                id="item-sku"
                                autoFocus
                                value={form.data.sku}
                                onChange={(event) => form.setData('sku', event.target.value)}
                                maxLength={64}
                                disabled={form.processing}
                                required
                                aria-invalid={Boolean(errors.sku)}
                            />
                            <InputError message={errors.sku} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="item-name">Nama barang</Label>
                            <Input
                                id="item-name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                maxLength={150}
                                disabled={form.processing}
                                required
                                aria-invalid={Boolean(errors.name)}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="item-base">Satuan dasar</Label>
                            <Select value={form.data.base_unit_id} onValueChange={chooseBase} disabled={form.processing || Boolean(record)}>
                                <SelectTrigger id="item-base" aria-invalid={Boolean(errors.base_unit_id)}>
                                    <SelectValue placeholder="Pilih satuan dasar" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.map((unit) => (
                                        <SelectItem key={unit.id} value={unit.id}>
                                            {unit.name} ({unit.symbol})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.base_unit_id} />
                        </div>
                        <div className="flex items-center gap-2 self-center">
                            <Checkbox
                                id="item-active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                disabled={form.processing}
                            />
                            <Label htmlFor="item-active">Aktif</Label>
                        </div>
                    </div>
                    <UnitEditor
                        units={form.data.units}
                        options={options}
                        baseUnitId={form.data.base_unit_id}
                        errors={errors}
                        disabled={form.processing}
                        onChange={(units) => form.setData('units', units)}
                    />
                    <InputError message={errors.inventory ?? errors.is_active} />
                    <DialogFooter>
                        <Button variant="outline" type="button" onClick={onClose} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing || !form.data.base_unit_id}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
