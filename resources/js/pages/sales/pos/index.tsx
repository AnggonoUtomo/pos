import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, RotateCcw } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { CartPanel } from './components/cart-panel';
import { ItemSearch } from './components/item-search';
import { OrderSummary } from './components/order-summary';
import { PaymentDrawer } from './components/payment-drawer';
import { levelPrice, lineSubtotal, selectedUnit } from './components/pos-format';
import { SaleContextBar } from './components/sale-context-bar';
import { catalogItems, customerLevels, initialPayments, warehouses } from './mock-data';
import { type CartLine } from './types';

export default function PosIndex() {
    const [warehouseId, setWarehouseId] = useState(warehouses[0].id);
    const [customerLevelId, setCustomerLevelId] = useState(customerLevels[0].id);
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState('Semua');
    const [cartLines, setCartLines] = useState<CartLine[]>([]);
    const [payments, setPayments] = useState(initialPayments);
    const [paymentOpen, setPaymentOpen] = useState(false);

    const customerLevel = customerLevels.find((level) => level.id === customerLevelId) ?? customerLevels[0];
    const selectedWarehouse = warehouses.find((warehouse) => warehouse.id === warehouseId) ?? warehouses[0];

    const subtotal = useMemo(
        () => cartLines.reduce((sum, line) => sum + line.qty * levelPrice(selectedUnit(line), customerLevel), 0),
        [cartLines, customerLevel],
    );
    const discount = cartLines.reduce((sum, line) => sum + line.discount, 0);
    const taxableAmount = cartLines.reduce((sum, line) => sum + lineSubtotal(line, customerLevel), 0);
    const tax = Math.round(taxableAmount * 0.11);
    const total = taxableAmount + tax;

    const addItem = (itemId: string): void => {
        const item = catalogItems.find((catalogItem) => catalogItem.id === itemId);

        if (!item) {
            return;
        }

        setCartLines((currentLines) => {
            const existingLine = currentLines.find((line) => line.item.id === item.id);

            if (existingLine) {
                return currentLines.map((line) => (line.item.id === item.id ? { ...line, qty: Math.min(line.qty + 1, item.stock) } : line));
            }

            return [...currentLines, { item, unitCode: item.units[0].code, qty: 1, discount: 0 }];
        });
    };

    const updateLine = (itemId: string, updater: (line: CartLine) => CartLine): void => {
        setCartLines((currentLines) => currentLines.map((line) => (line.item.id === itemId ? updater(line) : line)));
    };

    const handleQtyChange = (itemId: string, qty: number): void => {
        updateLine(itemId, (line) => {
            const maxQty = Math.max(1, Math.floor(line.item.stock / selectedUnit(line).ratio));

            return { ...line, qty: Math.min(Math.max(1, Number.isFinite(qty) ? qty : 1), maxQty) };
        });
    };

    const handleUnitChange = (itemId: string, unitCode: string): void => {
        updateLine(itemId, (line) => {
            const nextUnit = line.item.units.find((unit) => unit.code === unitCode) ?? line.item.units[0];
            const maxQty = Math.max(1, Math.floor(line.item.stock / nextUnit.ratio));

            return { ...line, unitCode, qty: Math.min(line.qty, maxQty) };
        });
    };

    const handleDiscountChange = (itemId: string, value: number): void => {
        updateLine(itemId, (line) => ({ ...line, discount: Math.max(0, Number.isFinite(value) ? value : 0) }));
    };

    const handleRemove = (itemId: string): void => {
        setCartLines((currentLines) => currentLines.filter((line) => line.item.id !== itemId));
    };

    const handlePaymentChange = (id: string, amount: number): void => {
        setPayments((currentPayments) =>
            currentPayments.map((payment) => (payment.id === id ? { ...payment, amount: Math.max(0, Number.isFinite(amount) ? amount : 0) } : payment)),
        );
    };

    const resetSale = (): void => {
        setCartLines([]);
        setPayments(initialPayments);
        setPaymentOpen(false);
        toast.success('Transaksi mock dikosongkan.');
    };

    const submitPayment = (): void => {
        toast.success('Pembayaran mock tersimpan. Belum ada posting transaksi backend.');
        setPaymentOpen(false);
    };

    return (
        <>
            <Head title="POS" />
            <main className="flex h-screen min-h-[720px] flex-col bg-muted/30 text-foreground">
                <header className="flex shrink-0 items-center justify-between border-b bg-background px-5 py-3">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-5 fill-current" />
                        </div>
                        <div>
                            <h1 className="text-lg font-semibold leading-none">POS</h1>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {selectedWarehouse.name} / {customerLevel.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/dashboard">
                                <ArrowLeft />
                                Admin
                            </Link>
                        </Button>
                        <Button variant="outline" onClick={resetSale} disabled={cartLines.length === 0}>
                            <RotateCcw />
                            Reset
                        </Button>
                    </div>
                </header>

                <div className="grid min-h-0 flex-1 gap-4 p-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(25rem,0.75fr)]">
                    <div className="grid min-h-0 grid-rows-[auto_minmax(0,1fr)] gap-4">
                        <SaleContext
                            warehouseId={warehouseId}
                            customerLevelId={customerLevelId}
                            onWarehouseChange={setWarehouseId}
                            onCustomerLevelChange={setCustomerLevelId}
                        />
                        <ItemSearch
                            items={catalogItems}
                            query={query}
                            category={category}
                            customerLevel={customerLevel}
                            onQueryChange={setQuery}
                            onCategoryChange={setCategory}
                            onAddItem={(item) => addItem(item.id)}
                        />
                    </div>

                    <div className="grid min-h-0 grid-rows-[minmax(0,1fr)_auto] gap-4">
                        <CartPanel
                            lines={cartLines}
                            customerLevel={customerLevel}
                            onQtyChange={handleQtyChange}
                            onUnitChange={handleUnitChange}
                            onDiscountChange={handleDiscountChange}
                            onRemove={handleRemove}
                        />
                        <OrderSummary subtotal={subtotal} discount={discount} tax={tax} total={total} onOpenPayment={() => setPaymentOpen(true)} />
                    </div>
                </div>
            </main>
            <PaymentDrawer
                open={paymentOpen}
                total={total}
                payments={payments}
                onOpenChange={setPaymentOpen}
                onPaymentChange={handlePaymentChange}
                onSubmit={submitPayment}
            />
        </>
    );
}

function SaleContext({
    warehouseId,
    customerLevelId,
    onWarehouseChange,
    onCustomerLevelChange,
}: {
    warehouseId: string;
    customerLevelId: string;
    onWarehouseChange: (value: string) => void;
    onCustomerLevelChange: (value: string) => void;
}) {
    return (
        <section className="rounded-lg border bg-background p-4">
            <SaleContextBar
                warehouses={warehouses}
                customerLevels={customerLevels}
                warehouseId={warehouseId}
                customerLevelId={customerLevelId}
                onWarehouseChange={onWarehouseChange}
                onCustomerLevelChange={onCustomerLevelChange}
            />
        </section>
    );
}
