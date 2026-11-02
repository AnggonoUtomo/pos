import InputError from '@/components/input-error';
import { Autocomplete } from '@/components/shared/autocomplete';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMasterCreate } from '@/hooks/use-master-create';
import { formatDecimalInput, parseDecimalInput } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { type FormEvent } from 'react';
import { toast } from 'sonner';
import { baseRow, type ItemPriceInput, type ItemRow, type ItemUnitInput, type PriceLevel, type UnitOption } from '../types';
import { ImageEditor } from './image-editor';
import { PriceEditor, priceMatrix } from './price-editor';
import { UnitEditor } from './unit-editor';

export function ItemFormDialog({
    record,
    options,
    levels,
    onClose,
    onCreated,
}: {
    record: ItemRow | null;
    options: UnitOption[];
    levels: PriceLevel[];
    onClose: () => void;
    onCreated?: (id: string) => void | Promise<void>;
}) {
    const initialUnits: ItemUnitInput[] =
        record?.units.map((unit) => ({
            unit_id: unit.unit_id,
            conversion_ratio: formatDecimalInput(unit.conversion_ratio),
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
        image: null as File | null,
        remove_image: false,
        prices: record?.prices.map((price) => ({ ...price, amount: formatDecimalInput(price.amount) })) ?? ([] as ItemPriceInput[]),
    });
    const creator = useMasterCreate(onCreated);
    const processing = form.processing || creator.processing;
    const errors = form.errors as Record<string, string>;
    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (processing) return;
        form.clearErrors();
        const units: ItemUnitInput[] = [];
        let invalidFormat = false;
        form.data.units.forEach((unit, index) => {
            const ratio = parseDecimalInput(unit.conversion_ratio);
            if (ratio === null) {
                invalidFormat = true;
                form.setError(`units.${index}.conversion_ratio`, 'Gunakan format angka Indonesia, misalnya 1 atau 1,5.');
            } else {
                units.push({ ...unit, conversion_ratio: ratio });
            }
        });
        const prices: ItemPriceInput[] = [];
        priceMatrix(form.data.units, levels, form.data.prices).forEach((price, index) => {
            const amount = parseDecimalInput(price.amount, 2);
            if (amount === null) {
                invalidFormat = true;
                form.setError(`prices.${index}.amount`, 'Isi harga dengan format rupiah Indonesia, maksimal dua desimal.');
            } else prices.push({ ...price, amount });
        });
        if (invalidFormat) {
            toast.error('Periksa format satuan dan harga jual.');
            return;
        }
        if (!record && onCreated) {
            if (!units.some((unit) => unit.is_purchase_unit)) {
                form.setError('units', 'Minimal satu satuan harus boleh dibeli.');
                return;
            }
            if (
                await creator.submit(
                    route('inventory.items.store'),
                    { ...form.data, units: units.map((unit) => ({ ...unit })), prices: prices.map((price) => ({ ...price })) },
                    (fields) => form.setError(fields as typeof form.errors),
                )
            )
                onClose();
            return;
        }
        form.transform((data) => ({ ...data, units, prices, ...(record && data.image ? { _method: 'put' } : {}) }));
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Barang berhasil diperbarui.' : 'Barang berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Barang belum tersimpan. Periksa isian form.'),
        };
        if (record && form.data.image) form.post(route('inventory.items.update', { id: record.id }), { ...config, forceFormData: true });
        else if (record) form.put(route('inventory.items.update', { id: record.id }), config);
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
                if (!open && !processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[90dvh] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-4xl"
                onEscapeKeyDown={(event) => {
                    if (processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (processing) event.preventDefault();
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
                    <fieldset disabled={processing || creator.created} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="item-sku">SKU</Label>
                                <Input
                                    id="item-sku"
                                    autoFocus
                                    value={form.data.sku}
                                    onChange={(event) => form.setData('sku', event.target.value)}
                                    maxLength={64}
                                    disabled={processing}
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
                                    disabled={processing}
                                    required
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="item-base">Satuan dasar</Label>
                                <Autocomplete
                                    value={form.data.base_unit_id}
                                    onValueChange={chooseBase}
                                    disabled={processing || Boolean(record)}
                                    id="item-base"
                                    aria-invalid={Boolean(errors.base_unit_id)}
                                    placeholder="Pilih satuan dasar"
                                    options={[
                                        options.map((unit) => ({
                                            value: unit.id,
                                            label: (
                                                <>
                                                    {unit.name} ({unit.symbol})
                                                </>
                                            ),
                                        })),
                                    ]}
                                />
                                <InputError message={errors.base_unit_id} />
                            </div>
                            <div className="flex items-center gap-2 self-center">
                                <Checkbox
                                    id="item-active"
                                    checked={form.data.is_active}
                                    onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                    disabled={processing || Boolean(onCreated)}
                                />
                                <Label htmlFor="item-active">Aktif</Label>
                            </div>
                        </div>
                        <ImageEditor
                            currentUrl={record?.image_url ?? null}
                            file={form.data.image}
                            removed={form.data.remove_image}
                            name={form.data.name}
                            disabled={processing}
                            error={errors.image ?? errors.remove_image}
                            onChange={(image, remove_image) => {
                                form.clearErrors('image', 'remove_image');
                                form.setData((data) => ({ ...data, image, remove_image }));
                            }}
                            onError={(message) => {
                                form.setError('image', message);
                                toast.error(message);
                            }}
                        />
                        <UnitEditor
                            units={form.data.units}
                            options={options}
                            baseUnitId={form.data.base_unit_id}
                            errors={errors}
                            disabled={processing}
                            onChange={(units) => form.setData('units', units)}
                        />
                        <InputError message={errors.inventory ?? errors.is_active} />
                        <PriceEditor
                            itemId={record?.id ?? null}
                            units={form.data.units}
                            levels={levels}
                            prices={form.data.prices}
                            options={options}
                            errors={errors}
                            disabled={processing}
                            onChange={(prices) => form.setData('prices', prices)}
                        />
                    </fieldset>
                    <InputError message={errors.master} />
                    <DialogFooter>
                        <Button variant="outline" type="button" onClick={onClose} disabled={processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing || !form.data.base_unit_id}>
                            {processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
