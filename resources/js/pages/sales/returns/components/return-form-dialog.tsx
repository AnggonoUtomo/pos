import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDecimalInput as format, parseDecimalInput as parse } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, RefreshCw, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import type { ReturnRow, SourceInvoice } from '../types';
import { InvoicePicker } from './invoice-picker';

export function ReturnFormDialog({ record, readOnly, onClose }: { record: ReturnRow | null; readOnly: boolean; onClose: () => void }) {
    const form = useForm({
        invoice_id: record?.invoice_id ?? '',
        version: record?.version ?? 1,
        notes: record?.notes ?? '',
        lines:
            record?.lines.map((line) => ({ invoice_line_id: line.invoice_line_id, quantity: format(line.quantity) })) ??
            ([] as { invoice_line_id: string; quantity: string }[]),
    });
    const [source, setSource] = useState<SourceInvoice | null>(
        record && readOnly ? { ...record.snapshot, lines: record.lines.map((line) => line.snapshot) } : null,
    );
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(false);
    const [retry, setRetry] = useState(0);
    const errors = form.errors as Record<string, string>;
    useEffect(() => {
        if (readOnly || !form.data.invoice_id) return;
        const controller = new AbortController();
        setSource(null);
        setLoading(true);
        setError(false);
        (async () => {
            try {
                const response = await fetch(route('sales.returns.invoice', { id: form.data.invoice_id }), {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Snapshot gagal dimuat.');
                const data = await response.json();
                if (!controller.signal.aborted) setSource(data);
            } catch {
                if (!controller.signal.aborted) setError(true);
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        })();
        return () => controller.abort();
    }, [form.data.invoice_id, readOnly, retry]);
    const disabled = readOnly || form.processing;
    const submit = () => {
        if (disabled || loading || error || !source) return;
        form.clearErrors();
        if (!form.data.lines.length) {
            form.setError('lines', 'Pilih minimal satu kuantitas retur.');
            toast.error('Draft belum lengkap.');
            return;
        }
        let invalid = false;
        for (const [index, line] of form.data.lines.entries()) {
            if (parse(line.quantity) === null) {
                form.setError(`lines.${index}.quantity` as keyof typeof form.data, 'Format angka Indonesia tidak valid.');
                invalid = true;
            }
        }
        if (invalid) {
            toast.error('Periksa format kuantitas.');
            return;
        }
        form.transform((data) => ({ ...data, lines: data.lines.map((line) => ({ ...line, quantity: parse(line.quantity)! })) }));
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Draft retur berhasil disimpan.');
                onClose();
            },
            onError: () => toast.error('Draft belum tersimpan. Periksa isian.'),
        };
        if (record) form.put(route('sales.returns.update', { id: record.id }), config);
        else form.post(route('sales.returns.store'), config);
    };
    const change = (id: string, quantity: string) =>
        form.setData('lines', [
            ...form.data.lines.filter((line) => line.invoice_line_id !== id),
            ...(quantity === '' ? [] : [{ invoice_line_id: id, quantity }]),
        ]);
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-5xl"
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>
                        {readOnly
                            ? record?.status === 'posted'
                                ? 'Detail retur penjualan'
                                : 'Detail draft retur'
                            : record
                              ? 'Edit draft retur'
                              : 'Tambah draft retur'}
                    </DialogTitle>
                    <DialogDescription className={record ? 'break-all' : 'sr-only'}>
                        {record ? `${record.number} / Revisi ${record.version}` : 'Draft retur penjualan'}
                    </DialogDescription>
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
                            e.currentTarget.requestSubmit();
                        }
                    }}
                >
                    {!record && (
                        <InvoicePicker
                            value={form.data.invoice_id}
                            disabled={disabled}
                            onChange={(invoice_id) => {
                                setSource(null);
                                form.setData((data) => ({ ...data, invoice_id, lines: [] }));
                                form.clearErrors();
                            }}
                        />
                    )}
                    <InputError message={errors.invoice_id ?? errors.version ?? errors.draft ?? errors.lines} />
                    {loading && (
                        <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                            <LoaderCircle className="size-4 animate-spin" />
                            Memuat snapshot...
                        </p>
                    )}
                    {error && (
                        <div role="alert" className="text-destructive flex flex-wrap items-center gap-2 text-sm">
                            Snapshot belum dapat dimuat.
                            <Button type="button" variant="outline" onClick={() => setRetry((n) => n + 1)}>
                                <RefreshCw className="size-4" />
                                Muat ulang snapshot
                            </Button>
                        </div>
                    )}
                    {source && (
                        <>
                            <dl className="bg-muted/20 grid min-w-0 gap-3 rounded-md border p-3 text-sm md:grid-cols-3">
                                <div className="min-w-0">
                                    <dt className="text-muted-foreground">Invoice</dt>
                                    <dd className="font-mono text-xs break-all">{source.number}</dd>
                                </div>
                                <div className="min-w-0">
                                    <dt className="text-muted-foreground">Pelanggan</dt>
                                    <dd className="break-words">{source.customer_name}</dd>
                                    <dd className="text-muted-foreground text-xs">{source.customer_level_name}</dd>
                                </div>
                                <div className="min-w-0">
                                    <dt className="text-muted-foreground">Gudang</dt>
                                    <dd className="break-words">{source.warehouse_name}</dd>
                                </div>
                            </dl>
                            <div className="min-w-0 overflow-x-auto rounded-md border">
                                <table className="w-full min-w-[680px] text-sm">
                                    <thead className="text-muted-foreground bg-muted/40 border-b">
                                        <tr>
                                            {['Barang', 'Satuan asal', 'Terjual', 'Harga asal', 'Kuantitas retur'].map((title) => (
                                                <th scope="col" key={title} className="px-3 py-2 text-left font-medium">
                                                    {title}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {source.lines.map((line) => {
                                            const index = form.data.lines.findIndex((value) => value.invoice_line_id === line.id);
                                            const value = form.data.lines[index]?.quantity ?? '';
                                            return (
                                                <tr key={line.id}>
                                                    <td className="max-w-52 px-3 py-3 break-words">
                                                        <span className="block font-medium">{line.name}</span>
                                                        <span className="text-muted-foreground text-xs">{line.sku}</span>
                                                    </td>
                                                    <td className="px-3 py-3">
                                                        <span className="block">{line.unit_name}</span>
                                                        <span className="text-muted-foreground text-xs">
                                                            {format(line.conversion_ratio)} {line.base_unit_name}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 py-3 tabular-nums">{format(line.quantity)}</td>
                                                    <td className="px-3 py-3 whitespace-nowrap tabular-nums">Rp {format(line.unit_price)}</td>
                                                    <td className="w-44 px-3 py-3">
                                                        {readOnly ? (
                                                            <span className="tabular-nums">{value || '-'}</span>
                                                        ) : (
                                                            <Input
                                                                aria-label={`Kuantitas retur ${line.sku}`}
                                                                inputMode="decimal"
                                                                maxLength={32}
                                                                value={value}
                                                                disabled={disabled}
                                                                onChange={(e) => change(line.id, e.target.value)}
                                                            />
                                                        )}
                                                        <InputError
                                                            message={
                                                                index >= 0
                                                                    ? (errors[`lines.${index}.quantity`] ?? errors[`lines.${index}.invoice_line_id`])
                                                                    : undefined
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                    <div className="space-y-2">
                        <Label htmlFor="return-notes">Catatan</Label>
                        <Input
                            id="return-notes"
                            value={form.data.notes}
                            maxLength={500}
                            disabled={disabled}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <DialogFooter className="items-center sm:justify-between">
                        <Badge
                            variant="outline"
                            className={
                                record?.status === 'posted'
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                    : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                            }
                        >
                            {record?.status === 'posted' ? 'Diterima' : 'Draft'}
                        </Badge>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                                Tutup
                            </Button>
                            {!readOnly && (
                                <Button type="submit" disabled={form.processing || loading || error || !source}>
                                    {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan draft
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
