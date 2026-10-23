import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatDecimalInput as format } from '@/lib/decimal-input';
import { Banknote, CheckCheck, LoaderCircle, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { useRefund } from '../hooks/use-refund';
import type { RefundPayment, RefundQuote, ReturnRow } from '../types';
import { canonical, micros, money, RefundPayments } from './refund-payments';

export function RefundDialog({ record, onClose, onSettled }: { record: ReturnRow; onClose: () => void; onSettled: () => void }) {
    const state = useRefund(record, onSettled);
    const locked = state.submitting || state.uncertain;
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !locked) onClose();
            }}
        >
            <DialogContent
                className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-2xl"
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        e.currentTarget.querySelector('form')?.requestSubmit();
                    }
                }}
                onEscapeKeyDown={(e) => {
                    if (locked) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (locked) e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{state.settlement ? 'Hasil penerimaan supplier' : 'Terima uang supplier'}</DialogTitle>
                    <DialogDescription className="break-all">{record.number}</DialogDescription>
                </DialogHeader>
                <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                    <dt className="text-muted-foreground">Pembelian asal</dt>
                    <dd className="font-mono text-xs break-all">{record.purchase_order_number}</dd>
                    <dt className="text-muted-foreground">Supplier</dt>
                    <dd className="break-words">{record.supplier_name}</dd>
                    <dt className="text-muted-foreground">Barang</dt>
                    <dd>Diserahkan</dd>
                </dl>
                {state.loading && (
                    <p role="status" className="flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat nominal refund...
                    </p>
                )}
                {state.error && (
                    <p role="alert" className="text-destructive text-sm">
                        {state.error}
                    </p>
                )}
                {state.settlement ? (
                    <>
                        <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">Penyelesaian</dt>
                            <dd className="font-medium text-emerald-700 dark:text-emerald-300">
                                {state.settlement.outcome === 'no_payment' ? 'Tanpa pengembalian uang' : 'Uang diterima penuh'}
                            </dd>
                            <dt className="text-muted-foreground">Total uang kembali</dt>
                            <dd className="font-semibold break-words">Rp {format(state.settlement.total)}</dd>
                            <dt className="text-muted-foreground">Waktu (WIB)</dt>
                            <dd>
                                {new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(
                                    new Date(state.settlement.settled_at),
                                )}
                            </dd>
                        </dl>
                        {state.settlement.payments.map((line) => (
                            <div key={line.method_id} className="flex min-w-0 justify-between gap-3 border-t pt-3 text-sm">
                                <span className="break-words">{line.method_name}</span>
                                <span className="break-words">Rp {format(line.amount)}</span>
                            </div>
                        ))}
                        <DialogFooter>
                            <Button variant="outline" onClick={onClose}>
                                Tutup
                            </Button>
                        </DialogFooter>
                    </>
                ) : state.quote ? (
                    <RefundConfirmation
                        key={state.quote.quote_fingerprint}
                        quote={state.quote}
                        locked={locked}
                        submitting={state.submitting}
                        uncertain={state.uncertain}
                        onSubmit={state.submit}
                        onClose={onClose}
                    />
                ) : (
                    !state.loading && (
                        <DialogFooter>
                            <Button variant="outline" onClick={onClose}>
                                Tutup
                            </Button>
                            <Button onClick={() => void state.reload()}>
                                <RefreshCw className="size-4 text-sky-300" />
                                Muat ulang quote
                            </Button>
                        </DialogFooter>
                    )
                )}
            </DialogContent>
        </Dialog>
    );
}

function RefundConfirmation({
    quote,
    locked,
    submitting,
    uncertain,
    onSubmit,
    onClose,
}: {
    quote: RefundQuote;
    locked: boolean;
    submitting: boolean;
    uncertain: boolean;
    onSubmit: (input?: { version: number; quote_fingerprint: string; payments: RefundPayment[] }) => Promise<void>;
    onClose: () => void;
}) {
    const zero = quote.total === '0.000000';
    const [rows, setRows] = useState<RefundPayment[]>(zero ? [] : [{ method_id: quote.methods[0]?.id ?? '', amount: format(quote.total) }]);
    const values = rows.map((row) => canonical(row.amount));
    const valid =
        zero ||
        (rows.length > 0 &&
            rows.every((row) => quote.methods.some((method) => method.id === row.method_id)) &&
            new Set(rows.map((row) => row.method_id)).size === rows.length &&
            values.every((value) => value !== null));
    const total = valid ? values.reduce<bigint>((sum, value) => sum + micros(value!), 0n) : null;
    const exact = zero || total === micros(quote.total);
    const submit = () => {
        if (submitting) return;
        if (uncertain) void onSubmit();
        else if (valid && exact)
            void onSubmit({
                version: quote.version,
                quote_fingerprint: quote.quote_fingerprint,
                payments: rows.map((row, i) => ({ method_id: row.method_id, amount: values[i]! })),
            });
    };
    return (
        <form
            className="min-w-0 space-y-4"
            onSubmit={(e) => {
                e.preventDefault();
                submit();
            }}
        >
            <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 border-y py-4 text-sm">
                <dt className="text-muted-foreground">Total uang kembali</dt>
                <dd className="text-lg font-semibold break-words">Rp {format(quote.total)}</dd>
            </dl>
            {zero ? (
                <p className="flex items-center gap-2 text-sm font-medium">
                    <CheckCheck className="size-4 text-emerald-600" />
                    Tanpa pengembalian uang
                </p>
            ) : (
                <RefundPayments quote={quote} rows={rows} onChange={setRows} disabled={locked} />
            )}
            {!zero && (
                <div className="flex min-w-0 flex-wrap justify-between gap-3 text-sm">
                    <span>Total metode</span>
                    <output aria-label="Total metode refund" className="font-medium break-words">
                        {total === null ? 'Jumlah belum valid' : `Rp ${format(money(total))}`}
                    </output>
                    {total !== null && !exact && (
                        <p role="status" className="text-destructive w-full">
                            {total > micros(quote.total)
                                ? 'Jumlah metode melebihi uang kembali.'
                                : `Sisa Rp ${format(money(micros(quote.total) - total))}`}
                        </p>
                    )}
                </div>
            )}
            <DialogFooter>
                <Button type="button" variant="outline" disabled={locked} onClick={onClose}>
                    Batal
                </Button>
                <Button type="submit" disabled={submitting || (!uncertain && (!valid || !exact))}>
                    {submitting ? (
                        <LoaderCircle className="size-4 animate-spin" />
                    ) : uncertain ? (
                        <RefreshCw className="size-4" />
                    ) : (
                        <Banknote className="size-4 text-emerald-300" />
                    )}
                    {submitting ? 'Memproses...' : uncertain ? 'Periksa ulang hasil' : zero ? 'Selesaikan tanpa uang' : 'Konfirmasi penerimaan'}
                </Button>
            </DialogFooter>
        </form>
    );
}
