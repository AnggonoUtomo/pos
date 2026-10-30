import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from './components/data-table';
import { DetailDialog } from './components/detail-dialog';
import { FilterBar } from './components/filter-bar';
import { usePaymentDetail } from './hooks/use-payment-detail';
import type { HistoryFilters, HistoryProps } from './types';

const empty: HistoryFilters = { search: '', direction: 'all', account_id: null, method_id: null, date_from: null, date_to: null };
export default function PaymentHistory({ records, filters, options }: HistoryProps) {
    const { can } = usePermission();
    const [draft, setDraft] = useState(filters);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const searchRef = useRef<HTMLInputElement>(null);
    const detail = usePaymentDetail();
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
            route('finance.payments.history.index'),
            { ...tablePageParams(), ...values, page },
            {
                preserveState: true,
                preserveScroll: true,
                onError: (errors) => {
                    setErrors(errors);
                    toast.error('Filter belum valid. Periksa isian.');
                },
            },
        );
    }, []);
    const apply = useCallback(() => {
        if (!loading) visit(draft);
    }, [draft, loading, visit]);
    useEffect(() => {
        const key = (e: KeyboardEvent) => {
            if (document.querySelector('[role=dialog]')) return;
            if (e.altKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                searchRef.current?.focus();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                apply();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [apply]);
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: route('finance.payments.history.index') },
                { title: 'Riwayat pembayaran', href: route('finance.payments.history.index') },
            ]}
        >
            <Head title="Riwayat pembayaran" />
            <WorkspaceShortcutDropdown
                title="Riwayat pembayaran"
                description={`${records.meta.total} dokumen`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus nomor transaksi', keys: ['Alt', 'F'] },
                    { label: 'Terapkan filter', description: 'Perbarui daftar', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Tutup detail', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <h1 className="text-xl font-semibold">Riwayat pembayaran</h1>
                <FilterBar
                    draft={draft}
                    options={options}
                    onChange={(patch) => setDraft((old) => ({ ...old, ...patch }))}
                    onApply={apply}
                    onReset={() => {
                        setDraft(empty);
                        visit(empty);
                    }}
                    loading={loading}
                    errors={errors}
                    searchRef={searchRef}
                />
                {loading && (
                    <div role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat...
                    </div>
                )}
                <DataTable rows={records.data} onOpen={(row) => void detail.load(row)} disabled={loading || !can('finance.payments.history.view')} />
                <TablePagination meta={records.meta} disabled={loading} />
            </section>
            <DetailDialog state={detail} />
        </AppLayout>
    );
}
