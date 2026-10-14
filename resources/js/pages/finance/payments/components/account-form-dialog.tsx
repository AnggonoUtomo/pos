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
import { accountTypes, type AccountRow, type AccountType } from '../types';

export function AccountFormDialog({ record, onClose }: { record: AccountRow | null; onClose: () => void }) {
    const form = useForm({
        code: record?.code ?? '',
        name: record?.name ?? '',
        type: record?.type ?? 'cash',
        bank_name: record?.bank_name ?? '',
        account_number: record?.account_number ?? '',
        account_holder: record?.account_holder ?? '',
        is_active: record?.is_active ?? true,
    });
    const errors = form.errors as Record<string, string>;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Akun berhasil diperbarui.' : 'Akun berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Akun belum tersimpan. Periksa isian form.'),
        };
        if (record) form.put(route('finance.payment-accounts.update', { id: record.id }), config);
        else form.post(route('finance.payment-accounts.store'), config);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[90dvh] overflow-y-auto sm:max-w-2xl"
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{record ? 'Edit' : 'Tambah'} akun kas dan bank</DialogTitle>
                    <DialogDescription className="sr-only">Form akun kas dan bank</DialogDescription>
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
                            <Label htmlFor="account-code">Kode</Label>
                            <Input
                                id="account-code"
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
                            <Label htmlFor="account-name">Nama akun</Label>
                            <Input
                                id="account-name"
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
                            <Label htmlFor="account-type">Jenis akun</Label>
                            <Select
                                value={form.data.type}
                                disabled={form.processing}
                                onValueChange={(type: AccountType) =>
                                    form.setData({
                                        ...form.data,
                                        type,
                                        ...(type === 'cash' ? { bank_name: '', account_number: '', account_holder: '' } : {}),
                                    })
                                }
                            >
                                <SelectTrigger id="account-type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(accountTypes).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
                        </div>
                        <div className="flex items-center gap-2 self-center">
                            <Checkbox
                                id="account-active"
                                checked={form.data.is_active}
                                disabled={form.processing}
                                onCheckedChange={(active) => form.setData('is_active', active === true)}
                            />
                            <Label htmlFor="account-active">Aktif</Label>
                        </div>
                    </div>
                    {form.data.type === 'bank' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {(
                                [
                                    ['bank_name', 'Nama bank', 150],
                                    ['account_number', 'Nomor rekening', 64],
                                    ['account_holder', 'Nama pemilik rekening', 150],
                                ] as const
                            ).map(([field, label, max]) => (
                                <div className="space-y-2" key={field}>
                                    <Label htmlFor={`account-${field}`}>{label}</Label>
                                    <Input
                                        id={`account-${field}`}
                                        maxLength={max}
                                        value={form.data[field]}
                                        disabled={form.processing}
                                        aria-invalid={Boolean(errors[field])}
                                        onChange={(event) => form.setData(field, event.target.value)}
                                    />
                                    <InputError message={errors[field]} />
                                </div>
                            ))}
                        </div>
                    )}
                    <InputError message={errors.payments ?? errors.is_active} />
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose} disabled={form.processing}>
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
