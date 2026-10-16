import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { LoaderCircle, Printer, RefreshCw } from 'lucide-react';
import { createPortal } from 'react-dom';
import { localDecimal, money } from '../lib/decimal';
import type { PostedInvoice } from '../types';
import './receipt.css';

function ReceiptBody({ receipt }: { receipt: PostedInvoice }) {
    return (
        <article className="space-y-4" data-testid="posted-receipt">
            <div className="flex flex-wrap items-start justify-between gap-3 border-b pb-3">
                <div className="min-w-0">
                    <p className="font-semibold break-all">{receipt.number}</p>
                    <p className="text-muted-foreground text-xs">{new Date(receipt.posted_at).toLocaleString('id-ID')}</p>
                </div>
                <Badge variant="outline" className="text-emerald-700 dark:text-emerald-300">
                    Lunas
                </Badge>
            </div>
            <dl className="grid grid-cols-[6rem_minmax(0,1fr)] gap-1.5 text-sm">
                <dt className="text-muted-foreground">Gudang</dt>
                <dd className="break-words">{receipt.warehouse_name}</dd>
                <dt className="text-muted-foreground">Pelanggan</dt>
                <dd className="break-words">{receipt.customer_name}</dd>
                <dt className="text-muted-foreground">Level</dt>
                <dd>{receipt.customer_level_name}</dd>
            </dl>
            <table className="w-full table-fixed text-xs">
                <thead>
                    <tr className="border-y text-left">
                        <th className="w-[35%] py-2 font-medium">Barang</th>
                        <th className="w-[15%] py-2 text-right font-medium">Qty</th>
                        <th className="w-[25%] py-2 text-right font-medium">Harga</th>
                        <th className="w-[25%] py-2 text-right font-medium">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    {receipt.lines.map((line) => (
                        <tr key={line.id} className="border-b align-top">
                            <td className="py-3 pr-2 break-words">
                                <span className="font-medium">{line.name}</span>
                                <span className="text-muted-foreground mt-1 block break-all">{line.sku}</span>
                            </td>
                            <td className="py-3 pl-1 text-right break-all">
                                {localDecimal(line.quantity)}
                                <span className="text-muted-foreground block break-words">{line.unit_name}</span>
                            </td>
                            <td className="py-3 pl-2 text-right break-all">{money(line.unit_price)}</td>
                            <td className="py-3 pl-2 text-right font-medium break-all">{money(line.subtotal)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <dl className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-2 text-sm">
                <dt>Subtotal</dt>
                <dd className="text-right break-all">{money(receipt.subtotal)}</dd>
                <dt>Diskon</dt>
                <dd className="text-right break-all">{money(receipt.discount_amount)}</dd>
                <dt>Pajak</dt>
                <dd className="text-right break-all">{money(receipt.tax_amount)}</dd>
                <dt className="border-t pt-2 font-semibold">Total</dt>
                <dd className="border-t pt-2 text-right font-semibold break-all">{money(receipt.total)}</dd>
            </dl>
            <div className="space-y-2 border-t pt-3 text-sm">
                {receipt.payments.payments.map((row) => (
                    <div key={row.method_id} className="flex justify-between gap-3">
                        <span className="min-w-0 break-words">{row.method_name}</span>
                        <span className="max-w-[60%] text-right break-all">{money(row.tendered_amount)}</span>
                    </div>
                ))}
                <div className="flex justify-between gap-3 font-medium">
                    <span>Dibayar</span>
                    <span className="max-w-[60%] text-right break-all">{money(receipt.payments.tendered)}</span>
                </div>
                <div className="flex justify-between gap-3 font-semibold text-emerald-700 dark:text-emerald-300">
                    <span>Kembalian</span>
                    <span className="max-w-[60%] text-right break-all">{money(receipt.payments.change)}</span>
                </div>
            </div>
        </article>
    );
}

export function ReceiptDialog({
    open,
    onOpenChange,
    receipt,
    loading,
    error,
    onReload,
}: {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    receipt: PostedInvoice | null;
    loading: boolean;
    error: string;
    onReload: () => void;
}) {
    return (
        <>
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent
                    className="flex max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl flex-col gap-4"
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'p' && receipt) {
                            event.preventDefault();
                            window.print();
                        }
                    }}
                >
                    <DialogHeader>
                        <DialogTitle>Bukti penjualan</DialogTitle>
                        <DialogDescription>Transaksi posted</DialogDescription>
                    </DialogHeader>
                    <div className="min-h-0 overflow-y-auto pr-1" aria-busy={loading}>
                        {loading && (
                            <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                                <LoaderCircle className="size-4 animate-spin" />
                                Memuat bukti transaksi...
                            </p>
                        )}
                        {error && (
                            <p role="alert" className="text-destructive text-sm">
                                {error}
                            </p>
                        )}
                        {receipt && <ReceiptBody receipt={receipt} />}
                    </div>
                    <DialogFooter className="shrink-0 gap-2">
                        <Button variant="outline" onClick={() => onOpenChange(false)}>
                            Selesai
                        </Button>
                        <Button variant="outline" disabled={loading} onClick={onReload} title="Muat ulang bukti" aria-label="Muat ulang bukti">
                            <RefreshCw className="text-sky-600 dark:text-sky-300" />
                        </Button>
                        <Button disabled={!receipt || loading} onClick={() => window.print()}>
                            <Printer />
                            Cetak
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            {open &&
                receipt &&
                typeof document !== 'undefined' &&
                createPortal(
                    <section className="pos-receipt-print">
                        <ReceiptBody receipt={receipt} />
                    </section>,
                    document.body,
                )}
        </>
    );
}
