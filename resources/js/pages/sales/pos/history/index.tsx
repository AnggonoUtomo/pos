import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { Button } from '@/components/ui/button';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { LoaderCircle, ShoppingCart } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { ReceiptDialog } from '../components/receipt-dialog';
import { DataTable } from './components/data-table';
import { FilterBar } from './components/filter-bar';
import { useInvoiceReceipt } from './hooks/use-invoice-receipt';
import type { HistoryFilters, HistoryPageProps } from './types';

const empty: HistoryFilters = { search: '', date_from: null, date_to: null, warehouse_id: null, customer_id: null };

export default function InvoiceHistory({ records, filters, viewAll, warehouseOptions, customerOptions }: HistoryPageProps) {
    const { can } = usePermission();
    const [draft, setDraft] = useState(filters);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const searchRef = useRef<HTMLInputElement>(null);
    const receipt = useInvoiceReceipt();
    useEffect(() => setDraft(filters), [filters]);
    useEffect(() => {
        const start = router.on('start', () => setLoading(true));
        const finish = router.on('finish', () => setLoading(false));
        return () => {
            start();
            finish();
        };
    }, []);
    const visit = useCallback((values: HistoryFilters, page = 1) => {
        setErrors({});
        router.get(
            route('sales.invoices.index'),
            { ...tablePageParams(), ...values, page },
            {
                preserveState: true,
                preserveScroll: true,
                onError: (errors) => {
                    setErrors(errors);
                    toast.error('Filter tidak valid. Periksa kembali isian.');
                },
            },
        );
    }, []);
    const apply = useCallback(() => {
        if (!loading) visit(draft);
    }, [draft, loading, visit]);
    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                searchRef.current?.focus();
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                apply();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [apply]);
    const meta = records.meta;
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Penjualan', href: route('sales.invoices.index') },
                { title: 'Riwayat penjualan', href: route('sales.invoices.index') },
            ]}
        >
            <Head title="Riwayat penjualan" />
            <WorkspaceShortcutDropdown
                title="Riwayat penjualan"
                description={viewAll ? 'Semua kasir' : 'Transaksi saya'}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus nomor invoice', keys: ['Alt', 'F'] },
                    { label: 'Terapkan filter', description: 'Perbarui daftar', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Cetak bukti', description: 'Pada dialog bukti', keys: ['Ctrl/Cmd', 'P'] },
                    { label: 'Tutup bukti', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Riwayat penjualan</h1>
                    {can('sales.pos.invoices.create') && (
                        <Button variant="outline" asChild>
                            <Link href={route('pos.index')}>
                                <ShoppingCart className="text-emerald-600 dark:text-emerald-300" />
                                POS
                            </Link>
                        </Button>
                    )}
                </div>
                <FilterBar
                    draft={draft}
                    onChange={(patch) => setDraft((old) => ({ ...old, ...patch }))}
                    onApply={apply}
                    onReset={() => {
                        setDraft(empty);
                        visit(empty);
                    }}
                    loading={loading}
                    warehouseOptions={warehouseOptions}
                    customerOptions={customerOptions}
                    searchRef={searchRef}
                />
                {Object.keys(errors).length > 0 && (
                    <div role="alert" className="text-destructive space-y-1 text-sm">
                        {Object.entries(errors).map(([key, message]) => (
                            <p key={key}>{message}</p>
                        ))}
                    </div>
                )}
                {loading && (
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Memuat penjualan...
                    </p>
                )}
                <DataTable rows={records.data} onOpen={(id) => void receipt.load(id)} loading={loading} />
                <TablePagination meta={meta} disabled={loading} />
                <ReceiptDialog
                    open={receipt.id !== null}
                    onOpenChange={receipt.close}
                    receipt={receipt.receipt}
                    loading={receipt.loading}
                    error={receipt.error}
                    onReload={receipt.reload}
                />
            </section>
        </AppLayout>
    );
}
