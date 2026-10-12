import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import type { CustomerRow, LevelOption } from '../types';

export function CustomerFormDialog({ record, levels, onClose }: { record: CustomerRow | null; levels: LevelOption[]; onClose: () => void }) {
    const form = useForm({
        code: record?.code ?? '',
        name: record?.name ?? '',
        customer_level_id: record?.customer_level_id ?? '',
        phone: record?.phone ?? '',
        email: record?.email ?? '',
        address: record?.address ?? '',
        is_active: record?.is_active ?? true,
    });
    const errors = form.errors as Record<string, string>;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Pelanggan berhasil diperbarui.' : 'Pelanggan berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Data belum tersimpan. Periksa isian form.'),
        };
        if (record) form.put(route('sales.customers.update', { id: record.id }), options);
        else form.post(route('sales.customers.store'), options);
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
                    <DialogTitle>{record ? 'Edit pelanggan' : 'Tambah pelanggan'}</DialogTitle>
                    <DialogDescription className="sr-only">Form pelanggan</DialogDescription>
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
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(
                            [
                                { key: 'code', label: 'Kode', max: 32 },
                                { key: 'name', label: 'Nama', max: 150 },
                            ] as const
                        ).map((field) => (
                            <div key={field.key} className="space-y-2">
                                <Label htmlFor={`customer-${field.key}`}>{field.label}</Label>
                                <Input
                                    id={`customer-${field.key}`}
                                    autoFocus={field.key === 'code'}
                                    required
                                    maxLength={field.max}
                                    value={form.data[field.key]}
                                    onChange={(e) => form.setData(field.key, e.target.value)}
                                    aria-invalid={Boolean(errors[field.key])}
                                />
                                <InputError message={errors[field.key]} />
                            </div>
                        ))}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="customer-level">Level pelanggan</Label>
                        <Select
                            value={form.data.customer_level_id}
                            onValueChange={(id) => form.setData('customer_level_id', id)}
                            disabled={form.processing}
                        >
                            <SelectTrigger id="customer-level" className="w-full" aria-invalid={Boolean(errors.customer_level_id)}>
                                <SelectValue placeholder="Pilih level" />
                            </SelectTrigger>
                            <SelectContent>
                                {record && !levels.some((level) => level.id === record.customer_level_id) && (
                                    <SelectItem value={record.customer_level_id} disabled>
                                        {record.customer_level_name} (nonaktif)
                                    </SelectItem>
                                )}
                                {levels.map((level) => (
                                    <SelectItem key={level.id} value={level.id}>
                                        {level.code} - {level.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.customer_level_id} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(
                            [
                                { key: 'phone', label: 'Telepon', max: 32, type: 'tel' },
                                { key: 'email', label: 'Email', max: 150, type: 'email' },
                            ] as const
                        ).map((field) => (
                            <div key={field.key} className="space-y-2">
                                <Label htmlFor={`customer-${field.key}`}>{field.label}</Label>
                                <Input
                                    id={`customer-${field.key}`}
                                    type={field.type}
                                    maxLength={field.max}
                                    value={form.data[field.key]}
                                    onChange={(e) => form.setData(field.key, e.target.value)}
                                    aria-invalid={Boolean(errors[field.key])}
                                />
                                <InputError message={errors[field.key]} />
                            </div>
                        ))}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="customer-address">Alamat</Label>
                        <Textarea
                            id="customer-address"
                            maxLength={500}
                            value={form.data.address}
                            onChange={(e) => form.setData('address', e.target.value)}
                            aria-invalid={Boolean(errors.address)}
                        />
                        <InputError message={errors.address} />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="customer-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                        />
                        <Label htmlFor="customer-active">Aktif</Label>
                    </div>
                    <InputError message={errors.parties ?? errors.is_active ?? errors.code} />
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing || levels.length === 0}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
