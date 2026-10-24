import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Banknote, CreditCard, LoaderCircle, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { localDecimal, money } from '../lib/decimal';
import { parsePaymentAmount, paymentSummary } from '../lib/payment';
import { posRequest } from '../lib/request';
import type { CartQuote, CheckoutAttempt, PaymentDraft, PaymentMethod } from '../types';

export function PaymentDialog({
    open,
    onOpenChange,
    quote,
    pending,
    submitting,
    error,
    canPost,
    onPay,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    quote: CartQuote | null;
    pending: CheckoutAttempt | null;
    submitting: boolean;
    error: string;
    canPost: boolean;
    onPay: (rows: PaymentDraft[], methods: PaymentMethod[]) => void;
}) {
    const [methods, setMethods] = useState<PaymentMethod[]>([]);
    const [rows, setRows] = useState<PaymentDraft[]>([]);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState('');
    const [version, setVersion] = useState(0);
    const total = pending?.total ?? quote?.total ?? '0.00';
    const fingerprint = quote?.fingerprint;
    useEffect(() => {
        if (!open || pending || !canPost || !fingerprint) return;
        const controller = new AbortController();
        setLoading(true);
        setLoadError('');
        setMethods([]);
        setRows([]);
        posRequest<{ methods: PaymentMethod[] }>(route('pos.payment-methods'), controller.signal)
            .then((data) => {
                if (controller.signal.aborted) return;
                setMethods(data.methods);
                const initial = data.methods.find((method) => method.type === 'cash') ?? data.methods[0];
                setRows(initial ? [{ methodId: initial.id, amount: localDecimal(total) }] : []);
            })
            .catch((failure: Error) => {
                if (!controller.signal.aborted) setLoadError(failure.message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [open, pending, canPost, fingerprint, total, version]);
    const displayedMethods = pending?.methods ?? methods;
    const displayedRows = pending ? pending.payload.payments.map((row) => ({ methodId: row.method_id, amount: localDecimal(row.amount) })) : rows;
    const summary = paymentSummary(total, displayedRows, displayedMethods);
    const locked = Boolean(pending) || submitting;
    const pay = () => {
        if (canPost && !submitting && (pending || (!loading && !loadError && summary.valid))) onPay(displayedRows, displayedMethods);
    };
    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                if (!locked) onOpenChange(value);
            }}
        >
            <DialogContent
                className={`flex max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl flex-col gap-4 ${locked ? '[&>button]:hidden' : ''}`}
                onKeyDown={(event) => {
                    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                        event.preventDefault();
                        pay();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>Pembayaran</DialogTitle>
                    <DialogDescription>
                        {pending ? 'Status belum dipastikan' : `${quote?.warehouse.name ?? ''} / ${quote?.customer.name ?? ''}`}
                    </DialogDescription>
                </DialogHeader>
                <div className="grid grid-cols-2 gap-x-6 gap-y-2 border-y py-3 text-sm" aria-live="polite">
                    <span>Tagihan</span>
                    <strong className="text-right break-all" data-testid="payment-total">
                        {money(total)}
                    </strong>
                    <span>Dibayar</span>
                    <span className="text-right break-all">{money(summary.tendered)}</span>
                    <span>Sisa</span>
                    <span className="text-right break-all text-amber-700 dark:text-amber-300" data-testid="payment-remaining">
                        {money(summary.remaining)}
                    </span>
                    <span>Kembalian</span>
                    <strong className="text-right break-all text-emerald-700 dark:text-emerald-300" data-testid="payment-change">
                        {money(summary.change)}
                    </strong>
                </div>
                <div className="min-h-0 space-y-3 overflow-y-auto pr-1">
                    {loading && !pending && (
                        <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                            <LoaderCircle className="size-4 animate-spin" />
                            Memuat metode pembayaran...
                        </p>
                    )}
                    {loadError && !pending && (
                        <p role="alert" className="text-destructive text-sm">
                            {loadError}
                        </p>
                    )}
                    {displayedRows.map((row, index) => (
                        <div key={index} className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_2.25rem] items-end gap-3">
                            <div className="min-w-0 space-y-1.5">
                                <Label htmlFor={`payment-method-${index}`}>Metode {index + 1}</Label>
                                <Select
                                    value={row.methodId}
                                    disabled={locked}
                                    onValueChange={(methodId) => setRows(rows.map((item, at) => (at === index ? { ...item, methodId } : item)))}
                                >
                                    <SelectTrigger id={`payment-method-${index}`}>
                                        <SelectValue placeholder="Pilih metode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {displayedMethods
                                            .filter(
                                                (method) => method.id === row.methodId || !displayedRows.some((item) => item.methodId === method.id),
                                            )
                                            .map((method) => (
                                                <SelectItem key={method.id} value={method.id}>
                                                    {method.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="min-w-0 space-y-1.5">
                                <Label htmlFor={`payment-amount-${index}`}>Nominal {index + 1}</Label>
                                <Input
                                    id={`payment-amount-${index}`}
                                    inputMode="decimal"
                                    maxLength={43}
                                    value={row.amount}
                                    disabled={locked}
                                    onBlur={() => {
                                        const amount = parsePaymentAmount(row.amount);
                                        if (amount)
                                            setRows(rows.map((item, at) => (at === index ? { ...item, amount: localDecimal(amount) } : item)));
                                    }}
                                    onChange={(event) =>
                                        setRows(rows.map((item, at) => (at === index ? { ...item, amount: event.target.value } : item)))
                                    }
                                />
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                title="Hapus metode"
                                aria-label={`Hapus metode ${index + 1}`}
                                disabled={locked || rows.length === 1}
                                onClick={() => setRows(rows.filter((_, at) => at !== index))}
                            >
                                <Trash2 className="text-rose-600 dark:text-rose-300" />
                            </Button>
                        </div>
                    ))}
                    {!pending && !loading && !loadError && methods.length === 0 && (
                        <p role="status" className="text-muted-foreground text-sm">
                            Tidak ada metode pembayaran aktif.
                        </p>
                    )}
                    {!pending && !loading && (
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={locked || rows.length >= 10 || !methods.some((method) => !rows.some((row) => row.methodId === method.id))}
                            onClick={() => {
                                const method = methods.find((method) => !rows.some((row) => row.methodId === method.id));
                                if (method) setRows([...rows, { methodId: method.id, amount: '' }]);
                            }}
                        >
                            <Plus className="text-sky-600 dark:text-sky-300" />
                            Tambah metode
                        </Button>
                    )}
                    {!pending && !loading && !loadError && summary.message && (
                        <p role="status" className="text-sm text-amber-700 dark:text-amber-300">
                            {summary.message}
                        </p>
                    )}
                    {error && (
                        <p role="alert" className="text-destructive text-sm">
                            {error}
                        </p>
                    )}
                    {!canPost && (
                        <p role="alert" className="text-destructive text-sm">
                            Akses posting tidak tersedia.
                        </p>
                    )}
                </div>
                <DialogFooter className="shrink-0 gap-2">
                    {!locked && (
                        <Button variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                    )}
                    {!pending && (loadError || methods.length === 0) && !loading && (
                        <Button variant="outline" onClick={() => setVersion((value) => value + 1)}>
                            <RefreshCw className="text-sky-600" />
                            Muat ulang metode
                        </Button>
                    )}
                    <Button onClick={pay} disabled={!canPost || submitting || (!pending && (loading || Boolean(loadError) || !summary.valid))}>
                        {submitting ? <LoaderCircle className="animate-spin" /> : pending ? <RefreshCw /> : <CreditCard />}
                        {submitting ? 'Memproses...' : pending ? 'Ulangi pembayaran' : 'Bayar dan posting'}
                        {!submitting && !pending && <Banknote className="text-emerald-300" />}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
