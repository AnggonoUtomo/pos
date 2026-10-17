import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatDecimalInput as format, parseDecimalInput as parse } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { Banknote, LoaderCircle, Plus, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { sumPurchaseValues } from '../lib/purchase-preview';
import type { PurchaseOptions, PurchaseRow } from '../types';

export function PaymentDialog({
    record,
    methods,
    onClose,
}: {
    record: PurchaseRow;
    methods: PurchaseOptions['paymentMethods'];
    onClose: () => void;
}) {
    const form = useForm({ version: record.version, payments: [{ method_id: methods[0]?.id ?? '', amount: format(record.total_value) }] });
    const errors = form.errors as Record<string, string>;
    const canonical = (value: string): string | null => {
        const parsed = parse(value);
        if (parsed === null || !/^\d{1,24}(?:\.\d{1,6})?$/.test(parsed)) return null;
        const [whole, fraction = ''] = parsed.split('.');
        return `${whole}.${fraction.padEnd(6, '0')}`;
    };
    const values = form.data.payments.map((row) => canonical(row.amount));
    const total = values.every((value) => value !== null) ? sumPurchaseValues(values as string[]) : null;
    const change = (index: number, patch: Partial<{ method_id: string; amount: string }>) =>
        form.setData((data) => ({ ...data, payments: data.payments.map((row, i) => (i === index ? { ...row, ...patch } : row)) }));
    const submit = () => {
        if (form.processing || methods.length === 0) return;
        form.clearErrors();
        let invalid = false;
        form.data.payments.forEach((row, index) => {
            if (canonical(row.amount) === null) {
                form.setError(`payments.${index}.amount` as keyof typeof form.data, 'Gunakan angka Indonesia yang valid.');
                invalid = true;
            }
        });
        if (invalid) {
            toast.error('Format jumlah pembayaran belum valid.');
            return;
        }
        form.transform((data) => ({ ...data, payments: data.payments.map((row) => ({ ...row, amount: canonical(row.amount)! })) }));
        form.post(route('purchasing.purchase-orders.pay', { id: record.id }), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Pembayaran pembelian berhasil dicatat.');
                onClose();
            },
            onError: () => toast.error('Pembayaran belum berhasil. Periksa isian.'),
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
                className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-2xl"
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>Bayar pembelian</DialogTitle>
                    <DialogDescription className="break-all">{record.number}</DialogDescription>
                </DialogHeader>
                <form
                    className="min-w-0 space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                    onKeyDown={(e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                            e.preventDefault();
                            submit();
                        }
                    }}
                >
                    <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                        <dt className="text-muted-foreground">Supplier</dt>
                        <dd className="break-words">{record.supplier_name}</dd>
                        <dt className="text-muted-foreground">Total pembelian</dt>
                        <dd className="font-semibold break-words">Rp {format(record.total_value)}</dd>
                    </dl>
                    <InputError message={errors.purchase_order ?? errors.version ?? errors.payments} />
                    {methods.length === 0 && (
                        <p role="alert" className="text-destructive text-sm">
                            Tidak ada metode pembayaran aktif.
                        </p>
                    )}
                    <fieldset disabled={form.processing} className="min-w-0 space-y-3">
                        <legend className="sr-only">Rincian pembayaran</legend>
                        {form.data.payments.map((row, index) => (
                            <div key={index} className="grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_2.25rem] items-start gap-2">
                                <div className="min-w-0 space-y-2">
                                    <Label htmlFor={`pay-method-${index}`}>Metode {index + 1}</Label>
                                    <Select
                                        value={row.method_id || undefined}
                                        onValueChange={(method_id) => change(index, { method_id })}
                                        disabled={form.processing}
                                    >
                                        <SelectTrigger id={`pay-method-${index}`} className="w-full min-w-0">
                                            <SelectValue placeholder="Pilih metode" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {methods.map((method) => (
                                                <SelectItem
                                                    key={method.id}
                                                    value={method.id}
                                                    disabled={form.data.payments.some((other, i) => i !== index && other.method_id === method.id)}
                                                >
                                                    {method.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors[`payments.${index}.method_id`]} />
                                </div>
                                <div className="min-w-0 space-y-2">
                                    <Label htmlFor={`pay-amount-${index}`}>Jumlah {index + 1}</Label>
                                    <Input
                                        id={`pay-amount-${index}`}
                                        inputMode="decimal"
                                        maxLength={50}
                                        value={row.amount}
                                        onChange={(e) => change(index, { amount: e.target.value })}
                                    />
                                    <InputError message={errors[`payments.${index}.amount`]} />
                                </div>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    className="mt-6"
                                    aria-label={`Hapus pembayaran ${index + 1}`}
                                    title={`Hapus pembayaran ${index + 1}`}
                                    disabled={form.processing || form.data.payments.length === 1}
                                    onClick={() =>
                                        form.setData(
                                            'payments',
                                            form.data.payments.filter((_, i) => i !== index),
                                        )
                                    }
                                >
                                    <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                </Button>
                            </div>
                        ))}
                    </fieldset>
                    <div className="flex min-w-0 flex-wrap items-center justify-between gap-3">
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            aria-label="Tambah metode pembayaran"
                            title="Tambah metode pembayaran"
                            disabled={form.processing || form.data.payments.length >= Math.min(10, methods.length)}
                            onClick={() => form.setData('payments', [...form.data.payments, { method_id: '', amount: '' }])}
                        >
                            <Plus className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                        <output aria-label="Total pembayaran" className="min-w-0 text-sm font-semibold break-words">
                            {total === null ? 'Jumlah belum valid' : `Rp ${format(total)}`}
                        </output>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing || methods.length === 0}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Banknote className="size-4 text-emerald-300" />}
                            Catat pembayaran
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
