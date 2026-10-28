import AppLogoIcon from '@/components/app-logo-icon';
import { CommandPalette } from '@/components/command-palette';
import { BarcodeInput } from '@/components/shared/barcode-input';
import { ThemeMenu } from '@/components/theme-menu';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ClipboardList, History, LayoutGrid, List, PauseCircle, ReceiptText, RotateCcw } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { CartPanel } from './components/cart-panel';
import { HeldCartDialogs } from './components/held-cart-dialogs';
import { ItemSearch } from './components/item-search';
import { OrderSummary } from './components/order-summary';
import { PaymentDialog } from './components/payment-dialog';
import { PosWorkspace } from './components/pos-workspace';
import { ReceiptDialog } from './components/receipt-dialog';
import { SaleContextBar } from './components/sale-context-bar';
import { useCatalog } from './hooks/use-catalog';
import { useHeldCarts } from './hooks/use-held-carts';
import { usePaidCheckout } from './hooks/use-paid-checkout';
import { usePosScanner } from './hooks/use-pos-scanner';
import { scanCart } from './lib/barcode';
import { checkoutKey } from './lib/checkout-key';
import { localDecimal, parsePricingAmount, parseQuantity } from './lib/decimal';
import { parsePaymentAmount } from './lib/payment';
import { PosRequestError, posRequest } from './lib/request';
import { initialPosContext, workspaceView, workspaceViewKey, type WorkspaceView } from './lib/workspace';
import type {
    CartLine,
    CartQuote,
    CheckoutAttempt,
    HeldCartRef,
    InvoicePricing,
    PaymentDraft,
    PaymentMethod,
    PosItem,
    PosPageProps,
    RecalledCart,
} from './types';

export default function PosIndex({ warehouses, customers }: PosPageProps) {
    const [warehouseId, setWarehouseId] = useState(() => initialPosContext(warehouses, customers).warehouseId);
    const [customerId, setCustomerId] = useState(() => initialPosContext(warehouses, customers).customerId);
    const [view, setView] = useState<WorkspaceView>('visual');
    useEffect(() => {
        try {
            setView(workspaceView(localStorage.getItem(workspaceViewKey)));
        } catch {
            // Storage can be disabled; the workspace remains usable without it.
        }
    }, []);
    const changeView = (value: string) => {
        if (!value) return;
        const next = workspaceView(value);
        setView(next);
        try {
            localStorage.setItem(workspaceViewKey, next);
        } catch {
            // Preference persistence is optional, not transaction persistence.
        }
    };
    const [query, setQuery] = useState('');
    const [page, setPage] = useState(1);
    const [catalogVersion, setCatalogVersion] = useState(0);
    const [lines, setLines] = useState<CartLine[]>([]);
    const lineState = useRef(lines);
    lineState.current = lines;
    const [heldSource, setHeldSource] = useState<HeldCartRef | null>(null);
    const [quote, setQuote] = useState<CartQuote | null>(null);
    const [pricing, setPricing] = useState<InvoicePricing>({ discount_type: 'fixed', discount_value: '0', tax_rate: '0' });
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
        setHeldSource(attempt.payload.held_cart ? { ...attempt.payload.held_cart, label: '' } : null);
        setWarehouseId(attempt.payload.warehouse_id);
        setCustomerId(attempt.payload.customer_id);
        setLines(attempt.cart);
        const source = attempt.payload.pricing;
        setPricing(
            source
                ? { ...source, discount_value: localDecimal(source.discount_value), tax_rate: localDecimal(source.tax_rate) }
                : { discount_type: 'fixed', discount_value: '0', tax_rate: '0' },
        );
        setPaymentOpen(true);
    }, []);
    const posted = useCallback(() => {
        invalidate();
        setLines([]);
        setHeldSource(null);
        setPricing({ discount_type: 'fixed', discount_value: '0', tax_rate: '0' });
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
    const checkoutLocked = Boolean(checkout.pending) || checkout.submitting || checkout.blocked || !checkout.ready;
    const scanner = usePosScanner(warehouseId, customerId, !checkoutLocked && !busy && !paymentOpen, (item, unitId) => {
        const next = scanCart(lineState.current, item, unitId);
        lineState.current = next;
        invalidate();
        setLines(next);
    });
    const applyHeld = (data: RecalledCart) => {
        invalidate();
        setWarehouseId(data.cart.warehouse_id);
        setCustomerId(data.cart.customer_id);
        setLines(data.cart.lines.map((line) => ({ ...line, quantity: localDecimal(line.quantity) })));
        setPricing({
            ...data.cart.pricing,
            discount_value: localDecimal(data.cart.pricing.discount_value),
            tax_rate: localDecimal(data.cart.pricing.tax_rate),
        });
        setHeldSource(data.source);
        setQuote(data.quote);
        setErrors(data.errors);
        setError(Object.values(data.errors)[0]?.[0] ?? '');
        setQuery('');
        setPage(1);
        setCatalogVersion((value) => value + 1);
    };
    const held = useHeldCarts({
        enabled: !checkoutLocked && !busy && !paymentOpen && !scanner.busy,
        hasLines: lines.length > 0,
        source: heldSource,
        draft: () => {
            const discount = parsePricingAmount(pricing.discount_value);
            const tax = parsePricingAmount(pricing.tax_rate);
            if (!ready || discount === null || tax === null) throw new PosRequestError('Periksa gudang, pelanggan, diskon dan pajak.');
            return {
                warehouse_id: warehouseId,
                customer_id: customerId,
                pricing: { discount_type: pricing.discount_type, discount_value: discount, tax_rate: tax },
                lines: lines.map((line) => {
                    const quantity = parseQuantity(line.quantity);
                    if (quantity === null) throw new PosRequestError('Periksa format kuantitas keranjang.');
                    return { item_id: line.item.id, unit_id: line.unitId, quantity };
                }),
            };
        },
        onHeld: posted,
        onRecall: applyHeld,
        onCancelled: (id) => {
            if (heldSource?.id === id) posted();
        },
    });
    const locked = checkoutLocked || held.busy || held.uncertain || scanner.busy;
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
                total: quote.total,
                cart: lines,
                methods: methods.filter((method) => rows.some((row) => row.methodId === method.id)),
                payload: {
                    ...(heldSource ? { held_cart: { id: heldSource.id, version: heldSource.version } } : {}),
                    idempotency_key: checkoutKey(),
                    warehouse_id: warehouseId,
                    customer_id: customerId,
                    quote_fingerprint: quote.fingerprint,
                    pricing: quote.pricing,
                    lines: lines.map((line) => ({ item_id: line.item.id, unit_id: line.unitId, quantity: parseQuantity(line.quantity)! })),
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
        const discountValue = parsePricingAmount(pricing.discount_value);
        const taxRate = parsePricingAmount(pricing.tax_rate);
        if (discountValue === null || taxRate === null) {
            toast.error('Diskon/pajak harus angka Indonesia maksimal dua desimal.');
            return;
        }
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
                pricing: { discount_type: pricing.discount_type, discount_value: discountValue, tax_rate: taxRate },
                lines: lines.map((line, index) => ({ item_id: line.item.id, unit_id: line.unitId, quantity: quantities[index] })),
            });
            if (!controller.signal.aborted) {
                setQuote(data);
                toast.success('Keranjang berhasil diperiksa.', { position: 'top-right' });
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
    }, [ready, lines, warehouseId, customerId, invalidate, locked, pricing]);

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
            if (event.altKey && event.key.toLowerCase() === 'b') {
                event.preventDefault();
                document.getElementById('pos-barcode')?.focus();
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !busy) {
                event.preventDefault();
                void check();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [check, busy, openPayment]);

    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.key === 'F6') {
                event.preventDefault();
                held.openHold();
            }
            if (event.key === 'F7') {
                event.preventDefault();
                held.openList();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [held]);

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
        scanner.enqueue(query);
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
            <main data-pos-view={view} className="text-foreground flex h-dvh min-h-[32rem] flex-col overflow-hidden bg-[var(--app-workspace)]">
                <header className="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-[var(--app-border)] bg-[var(--app-topbar)] px-4 py-2">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary-solid text-primary-solid-foreground flex size-9 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-5 fill-current" />
                        </div>
                        <h1 className="text-lg font-semibold">POS</h1>
                        <ToggleGroup
                            type="single"
                            value={view}
                            onValueChange={changeView}
                            disabled={locked || busy || paymentOpen}
                            aria-label="Tampilan POS"
                            variant="outline"
                            size="sm"
                        >
                            <ToggleGroupItem value="classic" aria-label="Tampilan Klasik">
                                <List className="mr-1 size-4 text-sky-600 dark:text-sky-300" />
                                Klasik
                            </ToggleGroupItem>
                            <ToggleGroupItem value="visual" aria-label="Tampilan Visual">
                                <LayoutGrid className="mr-1 size-4 text-teal-600 dark:text-teal-300" />
                                Visual
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </div>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        <CommandPalette />
                        <ThemeMenu />
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={locked || busy || paymentOpen || !ready || !lines.length}
                            onClick={held.openHold}
                            aria-label="Tahan"
                            title="Tahan (F6)"
                        >
                            <PauseCircle className="text-amber-600 dark:text-amber-300" />
                            <span className="hidden xl:inline">Tahan</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={locked || busy || paymentOpen}
                            onClick={held.openList}
                            aria-label="Tertahan"
                            title="Tertahan (F7)"
                        >
                            <ClipboardList className="text-sky-600 dark:text-sky-300" />
                            <span className="hidden xl:inline">Tertahan</span>
                        </Button>
                        {canAny(['sales.pos.invoices.post', 'sales.pos.invoices.view']) && (
                            <Button variant="outline" size="sm" asChild disabled={locked}>
                                <Link
                                    href={route('sales.invoices.index')}
                                    aria-label="Riwayat"
                                    title="Riwayat penjualan"
                                    aria-disabled={locked}
                                    onClick={(event) => {
                                        if (locked) event.preventDefault();
                                    }}
                                >
                                    <History className="text-sky-600 dark:text-sky-300" />
                                    <span className="hidden xl:inline">Riwayat</span>
                                </Link>
                            </Button>
                        )}
                        {checkout.lastId && (
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={locked}
                                onClick={() => void checkout.openReceipt()}
                                aria-label="Bukti terakhir"
                                title="Bukti terakhir"
                            >
                                <ReceiptText className="text-emerald-600 dark:text-emerald-300" />
                                <span className="hidden xl:inline">Bukti terakhir</span>
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild disabled={locked}>
                            <Link
                                href="/dashboard"
                                aria-label="Admin"
                                title="Kembali ke admin"
                                aria-disabled={locked}
                                onClick={(event) => {
                                    if (locked) event.preventDefault();
                                }}
                            >
                                <ArrowLeft className="text-sky-600 dark:text-sky-300" />
                                <span className="hidden xl:inline">Admin</span>
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!lines.length || locked}
                            onClick={() => setResetOpen(true)}
                            aria-label="Reset"
                            title="Kosongkan keranjang"
                        >
                            <RotateCcw className="text-amber-600 dark:text-amber-300" />
                            <span className="hidden xl:inline">Reset</span>
                        </Button>
                    </div>
                </header>
                <WorkspaceShortcutDropdown
                    className="shrink-0 py-1.5"
                    title="Operasi POS"
                    description={checkout.pending ? 'Pembayaran belum terkonfirmasi' : 'Belum diposting'}
                    shortcuts={[
                        { label: 'Cari barang', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                        { label: 'Scan barcode', description: 'Fokus scanner', keys: ['Alt', 'B'] },
                        { label: 'Masukkan scan', description: 'Pada field barcode', keys: ['Enter'] },
                        { label: 'Tahan keranjang', description: 'Simpan keranjang kasir', keys: ['F6'] },
                        { label: 'Keranjang tertahan', description: 'Panggil keranjang kasir', keys: ['F7'] },
                        { label: 'Periksa keranjang', description: 'Validasi stok dan harga', keys: ['Ctrl', 'Enter'] },
                        { label: 'Pembayaran', description: 'Pembayaran keranjang', keys: ['F8'], disabled: !canPost },
                        { label: 'Posting pembayaran', description: 'Pada dialog pembayaran', keys: ['Ctrl', 'Enter'], disabled: !canPost },
                        { label: 'Cetak bukti', description: 'Pada dialog bukti', keys: ['Ctrl', 'P'] },
                        { label: 'Command palette', description: 'Navigasi', keys: ['Ctrl', 'K'] },
                    ]}
                />
                {heldSource && (
                    <div className="border-b border-[var(--app-border)] px-5 py-2 text-sm text-amber-700 dark:text-amber-300" role="status">
                        Keranjang tertahan: {heldSource.label || heldSource.id.slice(-8)}
                    </div>
                )}
                {checkout.blocked && (
                    <p role="alert" className="text-destructive px-5 py-3 text-sm">
                        {checkout.error}
                    </p>
                )}
                <PosWorkspace
                    view={view}
                    locked={locked}
                    count={lines.length}
                    scanner={
                        <BarcodeInput
                            id="pos-barcode"
                            disabled={!ready || checkoutLocked || busy || paymentOpen || held.busy || held.uncertain}
                            busy={scanner.busy}
                            onScan={scanner.enqueue}
                        />
                    }
                    context={
                        <SaleContextBar
                            compact={view === 'visual'}
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
                    }
                    catalog={
                        <ItemSearch
                            visual={view === 'visual'}
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
                    }
                    cart={
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
                    }
                    summary={
                        <OrderSummary
                            pricing={pricing}
                            onPricing={(next) => {
                                invalidate();
                                setPricing(next);
                            }}
                            quote={quote}
                            loading={busy}
                            disabled={!ready || !lines.length}
                            error={error}
                            onCheck={() => void check()}
                            onPay={openPayment}
                            canPay={canPost}
                            locked={locked}
                        />
                    }
                />
            </main>
            <HeldCartDialogs held={held} />
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
                                setHeldSource(null);
                                setPricing({ discount_type: 'fixed', discount_value: '0', tax_rate: '0' });
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
