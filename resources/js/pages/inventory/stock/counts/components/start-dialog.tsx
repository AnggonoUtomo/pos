import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { LockKeyhole } from 'lucide-react';
import { toast } from 'sonner';
import type { CountIndexProps } from '../types';

export function StartDialog({
    warehouses,
    operationKey,
    onClose,
}: {
    warehouses: CountIndexProps['warehouses'];
    operationKey: string;
    onClose: () => void;
}) {
    const form = useForm({ warehouse_id: '', notes: '', operation_key: operationKey });
    const submit = () => {
        if (form.processing) return;
        form.post(route('inventory.stock.counts.store'), {
            onSuccess: () => toast.success('Opname dimulai. Mutasi stok gudang ditahan.'),
            onError: () => toast.error('Opname belum dimulai. Periksa isian atau sesi aktif.'),
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
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        submit();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>Mulai stock opname</DialogTitle>
                    <DialogDescription>
                        Mutasi stok gudang terpilih dihentikan sampai opname selesai atau dibatalkan. Gudang lain tetap berjalan.
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
                        <Label>Gudang</Label>
                        <Select value={form.data.warehouse_id} onValueChange={(id) => form.setData('warehouse_id', id)}>
                            <SelectTrigger aria-label="Gudang opname" className="w-full">
                                <SelectValue placeholder="Pilih gudang" />
                            </SelectTrigger>
                            <SelectContent>
                                {warehouses.map((row) => (
                                    <SelectItem key={row.id} value={row.id}>
                                        {row.code} - {row.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.warehouse_id} />
                        <Label htmlFor="count-notes">Catatan sesi</Label>
                        <Input
                            id="count-notes"
                            value={form.data.notes}
                            maxLength={500}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            required
                        />
                        {Object.entries(form.errors)
                            .filter(([key]) => key !== 'warehouse_id')
                            .map(([key, message]) => (
                                <InputError key={key} message={message} />
                            ))}
                    </fieldset>
                    <DialogFooter>
                        <Button variant="outline" type="button" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button disabled={form.processing}>
                            <LockKeyhole className="text-amber-200" />
                            Mulai dan tahan gudang
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
