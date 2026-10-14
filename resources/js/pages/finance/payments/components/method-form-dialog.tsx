import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { methodTypes, type AccountOption, type MethodRow, type MethodType } from '../types';

export function MethodFormDialog({ record, accounts, onClose }: { record: MethodRow | null; accounts: AccountOption[]; onClose: () => void }) {
    const form = useForm({
        code: record?.code ?? '',
        name: record?.name ?? '',
        type: record?.type ?? 'cash',
        payment_account_id: record?.payment_account_id ?? '',
        is_active: record?.is_active ?? true,
    });
    const options = accounts.filter((account) => account.type === (form.data.type === 'cash' ? 'cash' : 'bank'));
    const errors = form.errors as Record<string, string>;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Metode berhasil diperbarui.' : 'Metode berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Metode belum tersimpan. Periksa isian form.'),
        };
        if (record) form.put(route('finance.payment-methods.update', { id: record.id }), config);
        else form.post(route('finance.payment-methods.store'), config);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[90dvh] overflow-y-auto sm:max-w-xl"
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{record ? 'Edit' : 'Tambah'} metode pembayaran</DialogTitle>
                    <DialogDescription className="sr-only">Form metode pembayaran</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={submit}
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="method-code">Kode</Label>
                            <Input
                                id="method-code"
                                autoFocus
                                required
                                maxLength={32}
                                value={form.data.code}
                                disabled={form.processing}
                                aria-invalid={Boolean(errors.code)}
                                onChange={(event) => form.setData('code', event.target.value)}
                            />
                            <InputError message={errors.code} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="method-name">Nama metode</Label>
                            <Input
                                id="method-name"
                                required
                                maxLength={150}
                                value={form.data.name}
                                disabled={form.processing}
                                aria-invalid={Boolean(errors.name)}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="method-type">Jenis metode</Label>
                            <Select
                                value={form.data.type}
                                disabled={form.processing}
                                onValueChange={(type: MethodType) => form.setData({ ...form.data, type, payment_account_id: '' })}
                            >
                                <SelectTrigger id="method-type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(methodTypes).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="method-account">Akun tujuan</Label>
                            <Select
                                value={form.data.payment_account_id}
                                disabled={form.processing}
                                onValueChange={(id) => form.setData('payment_account_id', id)}
                            >
                                <SelectTrigger id="method-account" aria-invalid={Boolean(errors.payment_account_id)}>
                                    <SelectValue placeholder="Pilih akun aktif" />
                                </SelectTrigger>
                                <SelectContent>
                                    {record &&
                                        form.data.payment_account_id === record.payment_account_id &&
                                        !options.some((account) => account.id === record.payment_account_id) && (
                                            <SelectItem value={record.payment_account_id} disabled>
                                                Akun tidak tersedia
                                            </SelectItem>
                                        )}
                                    {options.map((account) => (
                                        <SelectItem key={account.id} value={account.id}>
                                            {account.code} - {account.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.payment_account_id} />
                        </div>
                    </div>
                    {options.length === 0 && (
                        <p role="status" className="text-sm text-amber-700 dark:text-amber-300">
                            Belum ada akun {form.data.type === 'cash' ? 'kas' : 'bank'} aktif.
                        </p>
                    )}
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="method-active"
                            checked={form.data.is_active}
                            disabled={form.processing}
                            onCheckedChange={(active) => form.setData('is_active', active === true)}
                        />
                        <Label htmlFor="method-active">Aktif</Label>
                    </div>
                    <InputError message={errors.payments ?? errors.is_active} />
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing || !form.data.payment_account_id}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
