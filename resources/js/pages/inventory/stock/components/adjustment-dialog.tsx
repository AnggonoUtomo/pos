import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { Check, LoaderCircle } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import type { StockOptions } from '../types';

const parse = (value: string) =>
    /^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,6})?$/.test(value.trim()) ? value.trim().replaceAll('.', '').replace(',', '.') : null;

export function AdjustmentDialog({ options, operationKey, onClose }: { options: StockOptions; operationKey: string; onClose: () => void }) {
    const form = useForm({ idempotency_key: operationKey, warehouse_id: '', item_id: '', direction: 'out', quantity: '', unit_cost: '', reason: '' });
    const busy = useRef(false);
    const errors = form.errors as Record<string, string>;
    const item = options.items.find((row) => row.id === form.data.item_id);
    const unit = options.units.find((row) => row.id === item?.base_unit_id)?.name;
    const submit = () => {
        if (busy.current || form.processing) return;
        const quantity = parse(form.data.quantity);
        const cost = form.data.direction === 'in' ? parse(form.data.unit_cost) : null;
        if (quantity === null || (form.data.direction === 'in' && cost === null)) {
            toast.error('Gunakan angka Indonesia, misalnya 1 atau 1,5.');
            return;
        }
        busy.current = true;
        form.transform((data) => ({ ...data, quantity, unit_cost: cost }));
        form.post(route('inventory.stock.adjustments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Koreksi stok berhasil diposting.');
                onClose();
            },
            onError: () => toast.error('Koreksi belum diposting. Periksa isian dan stok.'),
            onFinish: () => {
                busy.current = false;
            },
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
                className="max-h-[90dvh] overflow-y-auto"
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        submit();
                    }
                }}
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>Koreksi stok</DialogTitle>
                    <DialogDescription>Posting selisih hitung fisik dalam satuan dasar. Tidak dapat diedit setelah diposting.</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                >
                    <fieldset disabled={form.processing} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Gudang</Label>
                            <Select value={form.data.warehouse_id} onValueChange={(id) => form.setData('warehouse_id', id)}>
                                <SelectTrigger aria-label="Gudang koreksi" className="w-full">
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
                            <InputError message={errors.warehouse_id} />
                        </div>
                        <div className="space-y-2">
                            <Label>Barang</Label>
                            <Select value={form.data.item_id} onValueChange={(id) => form.setData('item_id', id)}>
                                <SelectTrigger aria-label="Barang koreksi" className="w-full">
                                    <SelectValue placeholder="Pilih barang" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.items.map((row) => (
                                        <SelectItem key={row.id} value={row.id}>
                                            {row.sku} - {row.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.item_id} />
                        </div>
                        <div className="flex gap-2" role="group" aria-label="Arah koreksi">
                            {(['out', 'in'] as const).map((direction) => (
                                <Button
                                    type="button"
                                    key={direction}
                                    variant={form.data.direction === direction ? 'secondary' : 'outline'}
                                    aria-pressed={form.data.direction === direction}
                                    onClick={() => form.setData('direction', direction)}
                                >
                                    {direction === 'out' ? 'Kurangi stok' : 'Tambah stok'}
                                </Button>
                            ))}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="adjust-quantity">Kuantitas selisih{unit ? ` (${unit})` : ''}</Label>
                            <Input
                                id="adjust-quantity"
                                inputMode="decimal"
                                value={form.data.quantity}
                                onChange={(e) => form.setData('quantity', e.target.value)}
                            />
                            <InputError message={errors.quantity} />
                        </div>
                        {form.data.direction === 'in' && (
                            <div className="space-y-2">
                                <Label htmlFor="adjust-cost">Harga pokok per {unit ?? 'satuan dasar'} (Rp)</Label>
                                <Input
                                    id="adjust-cost"
                                    inputMode="decimal"
                                    value={form.data.unit_cost}
                                    onChange={(e) => form.setData('unit_cost', e.target.value)}
                                />
                                <InputError message={errors.unit_cost} />
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="adjust-reason">Alasan koreksi</Label>
                            <Input
                                id="adjust-reason"
                                maxLength={500}
                                value={form.data.reason}
                                onChange={(e) => form.setData('reason', e.target.value)}
                            />
                            <InputError message={errors.reason} />
                        </div>
                    </fieldset>
                    <InputError message={errors.adjustment ?? errors.idempotency_key} />
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button disabled={form.processing}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Check className="size-4" />}Posting koreksi
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
