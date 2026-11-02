import InputError from '@/components/input-error';
import { Autocomplete } from '@/components/shared/autocomplete';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDecimalInput, parseDecimalInput } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { toast } from 'sonner';
import type { MinimumProps, MinimumRow } from '../types';

export function MinimumDialog({ row, options, onClose }: { row: MinimumRow | null; options: MinimumProps['options']; onClose: () => void }) {
    const form = useForm({
        warehouse_id: row?.warehouse_id ?? '',
        item_id: row?.item_id ?? '',
        minimum_quantity: row ? formatDecimalInput(row.minimum_quantity) : '',
        is_active: row?.is_active ?? true,
        version: row?.version ?? 0,
    });
    const item = options.items.find((item) => item.id === form.data.item_id);
    const unit = row?.unit_name ?? options.units.find((unit) => unit.id === item?.base_unit_id)?.name;
    const submit = () => {
        if (form.processing) return;
        const quantity = parseDecimalInput(form.data.minimum_quantity);
        if (quantity === null) {
            form.setError('minimum_quantity', 'Isi angka non-negatif, maksimal enam angka di belakang koma.');
            return;
        }
        form.transform((data) => ({ ...data, minimum_quantity: quantity }));
        const callbacks = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Stok minimum tersimpan.');
                onClose();
            },
            onError: () => toast.error('Belum tersimpan. Periksa isian atau muat ulang data.'),
        };
        if (row) form.put(route('inventory.stock.minimums.update', row.id), callbacks);
        else form.post(route('inventory.stock.minimums.store'), callbacks);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        submit();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>{row ? 'Ubah stok minimum' : 'Tambah stok minimum'}</DialogTitle>
                    <DialogDescription>
                        {row ? `${row.sku} - ${row.item_name} / ${row.warehouse_name}` : 'Atur batas dalam satuan dasar untuk satu gudang.'}
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                >
                    <fieldset disabled={form.processing} className="space-y-4">
                        {!row && (
                            <>
                                <Label>Gudang</Label>
                                <Autocomplete
                                    value={form.data.warehouse_id}
                                    onValueChange={(id) => form.setData('warehouse_id', id)}
                                    aria-label="Gudang stok minimum"
                                    className="w-full"
                                    placeholder="Pilih gudang"
                                    options={[
                                        options.warehouses.map((w) => ({
                                            value: w.id,
                                            label: (
                                                <>
                                                    {w.code} - {w.name}
                                                </>
                                            ),
                                        })),
                                    ]}
                                />
                                <Label htmlFor="minimum-item">Barang</Label>
                                <Autocomplete
                                    id="minimum-item"
                                    value={form.data.item_id}
                                    onValueChange={(id) => form.setData('item_id', id)}
                                    aria-label="Barang stok minimum"
                                    className="w-full"
                                    placeholder="Pilih barang"
                                    options={[
                                        options.items.map((i) => ({
                                            value: i.id,
                                            label: (
                                                <>
                                                    {i.sku} - {i.name}
                                                </>
                                            ),
                                        })),
                                    ]}
                                />
                            </>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="minimum-quantity">Minimum {unit ? `(${unit})` : '(satuan dasar)'}</Label>
                            <Input
                                id="minimum-quantity"
                                value={form.data.minimum_quantity}
                                inputMode="decimal"
                                maxLength={30}
                                placeholder="0"
                                onChange={(e) => form.setData('minimum_quantity', e.target.value)}
                                required
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="minimum-active"
                                checked={form.data.is_active}
                                onCheckedChange={(value) => form.setData('is_active', value === true)}
                            />
                            <Label htmlFor="minimum-active">Peringatan aktif</Label>
                        </div>
                        {Object.entries(form.errors).map(([key, message]) => (
                            <InputError key={key} message={message} />
                        ))}
                    </fieldset>
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button disabled={form.processing}>
                            <Save />
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
