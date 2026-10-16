import AppLogoIcon from '@/components/app-logo-icon';
import { CommandPalette } from '@/components/command-palette';
import { ThemeMenu } from '@/components/theme-menu';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, History, ReceiptText, RotateCcw } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { CartPanel } from './components/cart-panel';
import { ItemSearch } from './components/item-search';
import { OrderSummary } from './components/order-summary';
import { PaymentDialog } from './components/payment-dialog';
import { ReceiptDialog } from './components/receipt-dialog';
import { SaleContextBar } from './components/sale-context-bar';
import { useCatalog } from './hooks/use-catalog';
import { usePaidCheckout } from './hooks/use-paid-checkout';
import { checkoutKey } from './lib/checkout-key';
import { parseQuantity } from './lib/decimal';
import { parsePaymentAmount } from './lib/payment';
import { PosRequestError, posRequest } from './lib/request';
import type { CartLine, CartQuote, CheckoutAttempt, PaymentDraft, PaymentMethod, PosItem, PosPageProps } from './types';

export default function PosIndex({ warehouses, customers }: PosPageProps) {
    const [warehouseId, setWarehouseId] = useState('');
    const [customerId, setCustomerId] = useState('');
    const [query, setQuery] = useState('');
    const [page, setPage] = useState(1);
    const [catalogVersion, setCatalogVersion] = useState(0);
    const [lines, setLines] = useState<CartLine[]>([]);
    const [quote, setQuote] = useState<CartQuote | null>(null);
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [error, setError] = useState('');
    const [resetOpen, setResetOpen] = useState(false);
    const [paymentOpen, setPaymentOpen] = useState(false);
    const { user, can, canAny } = usePermission();
    const canPost = can('sales.pos.invoices.post');
    const request = useRef<AbortController | null>(null);
    const { result, loading, error: catalogError } = useCatalog(warehouseId, customerId, query, page, catalogVersion);
    const ready = Boolean(warehouseId && customerId);

    const invalidate = useCallback(() => {
        request.current?.abort();
        setQuote(null);
        setErrors({});
        setError('');
        setBusy(false);
    }, []);
    const recovered = useCallback((attempt: CheckoutAttempt) => {
        setWarehouseId(attempt.payload.warehouse_id);
        setCustomerId(attempt.payload.customer_id);
        setLines(attempt.cart);
        setPaymentOpen(true);
    }, []);
    const posted = useCallback(() => {
        invalidate();
        setLines([]);
        setQuery('');
        setPage(1);
        setPaymentOpen(false);
        setCatalogVersion((value) => value + 1);
    }, [invalidate]);
    const rejected = useCallback(
        (message: string) => {
            invalidate();
            setError(message);
            setPaymentOpen(false);
        },
        [invalidate],
    );
    const checkout = usePaidCheckout(user?.id, canPost, recovered, posted, rejected);
    const locked = Boolean(checkout.pending) || checkout.submitting || checkout.blocked || !checkout.ready;
    const clearPaymentError = checkout.clearError;
    const openPayment = useCallback(() => {
        if (canPost && quote && !busy && !locked) {
            clearPaymentError();
            setPaymentOpen(true);
        }
    }, [canPost, quote, busy, locked, clearPaymentError]);
    const pay = (rows: PaymentDraft[], methods: PaymentMethod[]) => {
        if (checkout.pending) {
            void checkout.submit(checkout.pending);
            return;
        }
        if (!quote || locked || !canPost) return;
        try {
            void checkout.submit({
                version: 1,
                total: quote.subtotal,
                cart: lines,
                methods: methods.filter((method) => rows.some((row) => row.methodId === method.id)),
                payload: {
                    idempotency_key: checkoutKey(),
                    warehouse_id: warehouseId,
                    customer_id: customerId,
                    quote_fingerprint: quote.fingerprint,
                    lines: quote.lines.map((line) => ({ item_id: line.item_id, unit_id: line.unit_id, quantity: line.quantity })),
                    payments: rows.map((row) => ({ method_id: row.methodId, amount: parsePaymentAmount(row.amount)! })),
                },
            });
        } catch {
            toast.error('Pembayaran belum dapat disiapkan.');
        }
    };
    useEffect(() => () => request.current?.abort(), []);

    const check = useCallback(async () => {
        if (!ready || !lines.length || locked) return;
        invalidate();
        const quantities = lines.map((line) => parseQuantity(line.quantity));
        if (quantities.some((value) => value === null)) {
            const errors: Record<string, string[]> = {};
            quantities.forEach((value, index) => {
                if (value === null) errors[`lines.${index}.quantity`] = ['Gunakan angka Indonesia, misalnya 1 atau 1,5.'];
            });
            setErrors(errors);
            toast.error('Periksa format kuantitas.');
            return;
        }
        const controller = new AbortController();
        request.current = controller;
        setBusy(true);
        try {
            const data = await posRequest<CartQuote>(route('pos.quote'), controller.signal, {
                warehouse_id: warehouseId,
                customer_id: customerId,
                lines: lines.map((line, index) => ({ item_id: line.item.id, unit_id: line.unitId, quantity: quantities[index] })),
            });
            if (!controller.signal.aborted) {
                setQuote(data);
                toast.success('Keranjang berhasil diperiksa.');
            }
        } catch (error) {
            if (!controller.signal.aborted) {
                const message = error instanceof Error ? error.message : 'Keranjang belum dapat diperiksa.';
                setError(message);
                if (error instanceof PosRequestError) setErrors(error.fields);
                toast.error(message);
            }
        } finally {
            if (!controller.signal.aborted) setBusy(false);
        }
    }, [ready, lines, warehouseId, customerId, invalidate, locked]);

    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.key === 'F8') {
                event.preventDefault();
                openPayment();
            }
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                document.getElementById('pos-search')?.focus();
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !busy) {
                event.preventDefault();
                void check();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [check, busy, openPayment]);

    const add = (item: PosItem, unitId: string) => {
        if (locked) return;
        if (lines.some((line) => line.item.id === item.id && line.unitId === unitId)) {
            toast.error('Barang dan satuan sudah ada di keranjang.');
            return;
        }
        if (lines.length >= 100) {
            toast.error('Maksimal 100 baris keranjang.');
            return;
        }
        invalidate();
        setLines([...lines, { item, unitId, quantity: '1' }]);
    };
    const barcode = () => {
        if (loading) return;
        const text = query.trim().toLowerCase();
        if (!text) return;
        for (const item of result?.items ?? []) {
            const unit =
                item.units.find((unit) => unit.barcode?.toLowerCase() === text) ??
                (item.sku.toLowerCase() === text ? (item.units.find((unit) => unit.id === item.base_unit_id) ?? item.units[0]) : undefined);
            if (unit?.price && /[1-9]/.test(item.stock)) {
                add(item, unit.id);
                return;
            }
        }
        toast.error('Barcode atau SKU belum tersedia untuk penjualan.');
    };
    const changeUnit = (index: number, unitId: string) => {
        if (locked) return;
        if (lines.some((line, i) => i !== index && line.item.id === lines[index].item.id && line.unitId === unitId)) {
            toast.error('Pasangan barang dan satuan sudah ada.');
            return;
        }
        invalidate();
        setLines(lines.map((line, i) => (i === index ? { ...line, unitId } : line)));
    };

    return (
        <>
            <Head title="POS" />
            <main className="text-foreground flex h-dvh min-h-[44rem] flex-col overflow-hidden bg-[var(--app-workspace)]">
                <header className="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-[var(--app-border)] bg-[var(--app-topbar)] px-5 py-3">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-5 fill-current" />
                        </div>
                        <h1 className="text-lg font-semibold">POS</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <CommandPalette />
                        <ThemeMenu />
                        {canAny(['sales.pos.invoices.post', 'sales.pos.invoices.view']) && (
                            <Button variant="outline" size="sm" asChild disabled={locked}>
                                <Link
                                    href={route('sales.invoices.index')}
                                    aria-disabled={locked}
                                    onClick={(event) => {
                                        if (locked) event.preventDefault();
                                    }}
                                >
                                    <History className="text-sky-600 dark:text-sky-300" />
                                    Riwayat
                                </Link>
                            </Button>
                        )}
                        {checkout.lastId && (
                            <Button variant="outline" size="sm" disabled={locked} onClick={() => void checkout.openReceipt()}>
                                <ReceiptText className="text-emerald-600 dark:text-emerald-300" />
                                Bukti terakhir
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild disabled={locked}>
                            <Link
                                href="/dashboard"
                                aria-disabled={locked}
                                onClick={(event) => {
                                    if (locked) event.preventDefault();
                                }}
                            >
                                <ArrowLeft className="text-sky-600 dark:text-sky-300" />
                                Admin
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" disabled={!lines.length || locked} onClick={() => setResetOpen(true)}>
                            <RotateCcw className="text-amber-600 dark:text-amber-300" />
                            Reset
                        </Button>
                    </div>
                </header>
                <WorkspaceShortcutDropdown
                    title="Operasi POS"
                    description={checkout.pending ? 'Pembayaran belum terkonfirmasi' : 'Belum diposting'}
                    shortcuts={[
                        { label: 'Cari barang', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                        { label: 'Periksa keranjang', description: 'Validasi stok dan harga', keys: ['Ctrl', 'Enter'] },
                        { label: 'Pembayaran', description: 'Pembayaran keranjang', keys: ['F8'], disabled: !canPost },
                        { label: 'Posting pembayaran', description: 'Pada dialog pembayaran', keys: ['Ctrl', 'Enter'], disabled: !canPost },
                        { label: 'Cetak bukti', description: 'Pada dialog bukti', keys: ['Ctrl', 'P'] },
                        { label: 'Command palette', description: 'Navigasi', keys: ['Ctrl', 'K'] },
                    ]}
                />
                {checkout.blocked && (
                    <p role="alert" className="text-destructive px-5 py-3 text-sm">
                        {checkout.error}
                    </p>
                )}
                <fieldset disabled={locked} className="contents">
                    <SaleContextBar
                        warehouses={warehouses}
                        customers={customers}
                        warehouseId={warehouseId}
                        customerId={customerId}
                        activeCustomer={quote?.customer ?? result?.customer}
                        onWarehouseChange={(id) => {
                            invalidate();
                            setWarehouseId(id);
                            setPage(1);
                        }}
                        onCustomerChange={(id) => {
                            invalidate();
                            setCustomerId(id);
                            setPage(1);
                        }}
                    />
                    <div className="grid min-h-0 flex-1 grid-rows-[minmax(0,1fr)_minmax(0,1fr)] lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:grid-rows-1">
                        <ItemSearch
                            items={result?.items ?? []}
                            query={query}
                            page={page}
                            hasMore={result?.has_more ?? false}
                            loading={loading}
                            error={catalogError}
                            ready={ready}
                            onQuery={(query) => {
                                setQuery(query);
                                setPage(1);
                            }}
                            onPage={setPage}
                            onAdd={add}
                            onBarcode={barcode}
                            onRetry={() => setCatalogVersion((value) => value + 1)}
                        />
                        <div className="flex min-h-0 min-w-0 flex-col bg-[var(--app-panel)]">
                            <CartPanel
                                lines={lines}
                                quote={quote}
                                errors={errors}
                                onQuantity={(index, quantity) => {
                                    invalidate();
                                    setLines(lines.map((line, i) => (i === index ? { ...line, quantity } : line)));
                                }}
                                onUnit={changeUnit}
                                onRemove={(index) => {
                                    invalidate();
                                    setLines(lines.filter((_, i) => i !== index));
                                }}
                            />
                            <OrderSummary
                                quote={quote}
                                loading={busy}
                                disabled={!ready || !lines.length}
                                error={error}
                                onCheck={() => void check()}
                                onPay={openPayment}
                                canPay={canPost}
                                locked={locked}
                            />
                        </div>
                    </div>
                </fieldset>
            </main>
            <PaymentDialog
                open={paymentOpen}
                onOpenChange={setPaymentOpen}
                quote={quote}
                pending={checkout.pending}
                submitting={checkout.submitting}
                error={checkout.error}
                canPost={canPost}
                onPay={pay}
            />
            <ReceiptDialog
                open={checkout.receiptOpen}
                onOpenChange={checkout.setReceiptOpen}
                receipt={checkout.receipt}
                loading={checkout.receiptBusy}
                error={checkout.receiptError}
                onReload={() => void checkout.openReceipt()}
            />
            <Dialog open={resetOpen} onOpenChange={setResetOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Kosongkan keranjang?</DialogTitle>
                        <DialogDescription>Seluruh baris keranjang akan dibuang.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={() => {
                                invalidate();
                                setLines([]);
                                setResetOpen(false);
                                toast.success('Keranjang dikosongkan.');
                            }}
                        >
                            Kosongkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
