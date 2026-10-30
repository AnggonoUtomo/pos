import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { LoaderCircle, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from './components/data-table';
import { LifecycleDialog } from './components/lifecycle-dialog';
import { PaymentDialog } from './components/payment-dialog';
import { PurchaseFormDialog } from './components/purchase-form-dialog';
import type { PurchaseFilters, PurchasePageProps, PurchaseRow } from './types';

export default function PurchaseOrders({ records, filters, options }: PurchasePageProps) {
    const { can } = usePermission();
    const canManage = can('purchasing.purchase-orders.manage');
    const canPost = can('purchasing.purchase-orders.post');
    const canPay = can('purchasing.purchase-orders.pay');
    const [paying, setPaying] = useState<PurchaseRow | null>(null);
    const [dialog, setDialog] = useState<{ record: PurchaseRow | null; readOnly: boolean } | null>(null);
    const [lifecycle, setLifecycle] = useState<PurchaseRow | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const searchRef = useRef<HTMLInputElement>(null);
    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);
    useEffect(() => {
        const start = router.on('start', () => setLoading(true));
        const finish = router.on('finish', () => setLoading(false));
        return () => {
            start();
            finish();
        };
    }, []);
    useEffect(() => {
        const keydown = (e: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (e.altKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                searchRef.current?.focus();
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'n' && canManage && !filters.trashed) {
                e.preventDefault();
                setDialog({ record: null, readOnly: false });
            }
        };
        window.addEventListener('keydown', keydown);
        return () => window.removeEventListener('keydown', keydown);
    }, [canManage, filters.trashed]);
    const visit = (patch: Partial<PurchaseFilters>, page = 1) =>
        router.get(
            route('purchasing.purchase-orders.index'),
            { ...tablePageParams(), ...filters, ...patch, trashed: (patch.trashed ?? filters.trashed) ? 1 : 0, page },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter belum valid.') },
        );
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pembelian', href: route('purchasing.purchase-orders.index') },
                { title: 'Order pembelian', href: route('purchasing.purchase-orders.index') },
            ]}
        >
            <Head title="Order pembelian" />
            <WorkspaceShortcutDropdown
                title="Order pembelian"
                description={`${records.meta.total} dokumen ${filters.trashed ? 'terhapus' : 'tersedia'}`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    { label: 'Scan barcode', description: 'Pada form draft', keys: ['Alt', 'B'], disabled: !canManage },
                    { label: 'Masukkan scan', description: 'Pada field barcode', keys: ['Enter'], disabled: !canManage },
                    {
                        label: 'Tambah',
                        description: 'Tambah draft pembelian',
                        keys: ['Ctrl/Cmd', 'Shift', 'N'],
                        disabled: !canManage || filters.trashed,
                    },
                    {
                        label: 'Simpan / Konfirmasi',
                        description: 'Operasi dialog aktif',
                        keys: ['Ctrl/Cmd', 'Enter'],
                        disabled: !canManage && !canPost && !canPay,
                    },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Order pembelian</h1>
                    {canManage && !filters.trashed && (
                        <Button disabled={loading} onClick={() => setDialog({ record: null, readOnly: false })}>
                            <Plus className="size-4" />
                            Tambah draft pembelian
                        </Button>
                    )}
                </div>
                <div className="flex flex-wrap gap-3">
                    <form
                        className="flex min-w-0 flex-1 gap-2 sm:min-w-64"
                        onSubmit={(e) => {
                            e.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            ref={searchRef}
                            aria-label="Cari pembelian"
                            value={search}
                            maxLength={150}
                            placeholder="Cari nomor, supplier, atau gudang..."
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </form>
                    {(['supplier', 'warehouse'] as const).map((kind) => {
                        const key = `${kind}_id` as const;
                        const label = kind === 'supplier' ? 'supplier' : 'gudang';
                        const choices = kind === 'supplier' ? options.suppliers : options.warehouses;
                        return (
                            <Select
                                key={kind}
                                value={filters[key] || 'all'}
                                onValueChange={(id) => visit({ [key]: id === 'all' ? '' : id })}
                                disabled={loading}
                            >
                                <SelectTrigger className="w-56" aria-label={`Filter ${label}`}>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua {label}</SelectItem>
                                    {filters[key] && !choices.some((choice) => choice.id === filters[key]) && (
                                        <SelectItem value={filters[key]}>Pilihan tidak aktif</SelectItem>
                                    )}
                                    {choices.map((choice) => (
                                        <SelectItem key={choice.id} value={choice.id}>
                                            {choice.code} - {choice.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        );
                    })}
                    <Select value={filters.status} onValueChange={(status: PurchaseFilters['status']) => visit({ status })} disabled={loading}>
                        <SelectTrigger className="w-40" aria-label="Filter status pembelian">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="posted">Diterima</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.payment_status}
                        onValueChange={(payment_status: PurchaseFilters['payment_status']) => visit({ payment_status })}
                        disabled={loading}
                    >
                        <SelectTrigger className="w-48" aria-label="Filter pembayaran">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua pembayaran</SelectItem>
                            <SelectItem value="unrecorded">Belum dicatat</SelectItem>
                            <SelectItem value="paid">Lunas</SelectItem>
                        </SelectContent>
                    </Select>
                    <div className="flex rounded-md border p-1" role="group" aria-label="Tampilan dokumen">
                        {[false, true].map((trashed) => (
                            <Button
                                key={String(trashed)}
                                variant={filters.trashed === trashed ? 'secondary' : 'ghost'}
                                aria-pressed={filters.trashed === trashed}
                                disabled={loading}
                                onClick={() => visit({ trashed })}
                            >
                                {trashed ? 'Terhapus' : 'Data'}
                            </Button>
                        ))}
                    </div>
                </div>
                {loading && (
                    <div role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat...
                    </div>
                )}
                <DataTable
                    rows={records.data}
                    canManage={canManage}
                    canPost={canPost}
                    canPay={canPay}
                    onPay={setPaying}
                    onOpen={(record, readOnly) => setDialog({ record, readOnly })}
                    onLifecycle={setLifecycle}
                />
                <TablePagination meta={records.meta} disabled={loading} />
            </section>
            {dialog && (dialog.readOnly || canManage) && (
                <PurchaseFormDialog
                    key={`${dialog.record?.id ?? 'new'}-${dialog.readOnly}`}
                    record={dialog.record}
                    options={options}
                    readOnly={dialog.readOnly || dialog.record?.status === 'posted'}
                    onClose={() => setDialog(null)}
                />
            )}
            {canManage && lifecycle && <LifecycleDialog key={lifecycle.id} record={lifecycle} onClose={() => setLifecycle(null)} />}
            {canPay && paying && <PaymentDialog key={paying.id} record={paying} methods={options.paymentMethods} onClose={() => setPaying(null)} />}
        </AppLayout>
    );
}
