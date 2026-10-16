import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import type { SupplierRow } from '../types';

export function SupplierFormDialog({ record, onClose }: { record: SupplierRow | null; onClose: () => void }) {
    const form = useForm({
        code: record?.code ?? '',
        name: record?.name ?? '',
        phone: record?.phone ?? '',
        email: record?.email ?? '',
        address: record?.address ?? '',
        is_active: record?.is_active ?? true,
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Supplier berhasil diperbarui.' : 'Supplier berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Data belum tersimpan. Periksa isian form.'),
        };
        if (record) form.put(route('purchasing.suppliers.update', { id: record.id }), options);
        else form.post(route('purchasing.suppliers.store'), options);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-xl"
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{record ? 'Edit supplier' : 'Tambah supplier'}</DialogTitle>
                    <DialogDescription className="sr-only">Form supplier</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={submit}
                    onKeyDown={(e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                            e.preventDefault();
                            e.currentTarget.requestSubmit();
                        }
                    }}
                >
                    <fieldset disabled={form.processing} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {(
                                [
                                    { key: 'code', label: 'Kode', max: 32, required: true, type: 'text' },
                                    { key: 'name', label: 'Nama', max: 150, required: true, type: 'text' },
                                    { key: 'phone', label: 'Telepon', max: 32, required: false, type: 'tel' },
                                    { key: 'email', label: 'Email', max: 150, required: false, type: 'email' },
                                ] as const
                            ).map((field) => (
                                <div key={field.key} className="space-y-2">
                                    <Label htmlFor={`supplier-${field.key}`}>{field.label}</Label>
                                    <Input
                                        id={`supplier-${field.key}`}
                                        type={field.type}
                                        autoFocus={field.key === 'code'}
                                        required={field.required}
                                        maxLength={field.max}
                                        value={form.data[field.key]}
                                        onChange={(e) => form.setData(field.key, e.target.value)}
                                        aria-invalid={Boolean(form.errors[field.key])}
                                    />
                                    <InputError message={form.errors[field.key]} />
                                </div>
                            ))}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="supplier-address">Alamat</Label>
                            <Textarea
                                id="supplier-address"
                                maxLength={500}
                                value={form.data.address}
                                onChange={(e) => form.setData('address', e.target.value)}
                                aria-invalid={Boolean(form.errors.address)}
                            />
                            <InputError message={form.errors.address} />
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="supplier-active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                disabled={form.processing}
                            />
                            <Label htmlFor="supplier-active">Aktif</Label>
                        </div>
                        <InputError message={form.errors.is_active} />
                    </fieldset>
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
