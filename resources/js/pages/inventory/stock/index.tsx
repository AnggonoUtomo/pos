import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, LoaderCircle, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from './components/data-table';
import { OpeningActionDialog } from './components/opening-action-dialog';
import { OpeningDialog } from './components/opening-dialog';
import type { StockFilters, StockPageProps, StockRow } from './types';

export default function StockIndex({ filters, records, options }: StockPageProps) {
    const { can } = usePermission();
    const canManage = can('inventory.stock.manage');
    const canPost = can('inventory.stock.post');
    const [dialog, setDialog] = useState<{ record: StockRow | null; readOnly: boolean } | null>(null);
    const [action, setAction] = useState<{ record: StockRow; action: 'post' | 'destroy' | 'restore' } | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const searchRef = useRef<HTMLInputElement>(null);
    useEffect(() => setSearch(filters.search), [filters.search]);
    useEffect(() => {
        const start = router.on('start', () => setLoading(true));
        const finish = router.on('finish', () => setLoading(false));
        return () => {
            start();
            finish();
        };
    }, []);
    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                searchRef.current?.focus();
            }
            if (
                (event.ctrlKey || event.metaKey) &&
                event.shiftKey &&
                event.key.toLowerCase() === 'n' &&
                canManage &&
                filters.tab === 'openings' &&
                !filters.trashed
            ) {
                event.preventDefault();
                setDialog({ record: null, readOnly: false });
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [canManage, filters.tab, filters.trashed]);
    const visit = (patch: Partial<StockFilters>, page = 1) => {
        const next = { ...filters, ...patch };
        router.get(
            route('inventory.stock.index'),
            { ...next, warehouse_id: next.warehouse_id || undefined, item_id: next.item_id || undefined, trashed: next.trashed ? 1 : 0, page },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter stok tidak valid.') },
        );
    };
    return (
        <AppLayout breadcrumbs={[{ title: 'Stok', href: route('inventory.stock.index') }]}>
            <Head title="Stok" />
            <WorkspaceShortcutDropdown
                title="Stok"
                description={`${records.meta.total} data`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    {
                        label: 'Tambah',
                        description: 'Tambah stok awal',
                        keys: ['Ctrl/Cmd', 'Shift', 'N'],
                        disabled: !canManage || filters.tab !== 'openings' || filters.trashed,
                    },
                    {
                        label: 'Simpan / Konfirmasi',
                        description: 'Operasi dialog aktif',
                        keys: ['Ctrl/Cmd', 'Enter'],
                        disabled: !canManage && !canPost,
                    },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Stok</h1>
                    {canManage && filters.tab === 'openings' && !filters.trashed && (
                        <Button disabled={loading} onClick={() => setDialog({ record: null, readOnly: false })}>
                            <Plus className="size-4" />
                            Tambah stok awal
                        </Button>
                    )}
                </div>
                <div className="flex w-fit flex-wrap rounded-md border p-1" role="group" aria-label="Tampilan stok">
                    {(
                        [
                            { key: 'openings', label: 'Stok awal' },
                            { key: 'balances', label: 'Saldo stok' },
                            { key: 'movements', label: 'Kartu stok' },
                        ] as const
                    ).map(({ key, label }) => (
                        <Button
                            key={key}
                            variant={filters.tab === key ? 'secondary' : 'ghost'}
                            aria-pressed={filters.tab === key}
                            disabled={loading}
                            onClick={() => visit({ tab: key, status: 'all', trashed: false })}
                        >
                            {label}
                        </Button>
                    ))}
                </div>
                <div className="flex flex-wrap gap-3">
                    <form
                        className="flex min-w-0 flex-1 gap-2 sm:min-w-64"
                        onSubmit={(event) => {
                            event.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            aria-label="Cari stok"
                            ref={searchRef}
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            maxLength={150}
                            placeholder={filters.tab === 'openings' ? 'Cari dokumen atau gudang...' : 'Cari SKU, barang, atau dokumen...'}
                        />
                        <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </form>
                    <Select
                        value={filters.warehouse_id || 'all'}
                        onValueChange={(id) => visit({ warehouse_id: id === 'all' ? '' : id })}
                        disabled={loading}
                    >
                        <SelectTrigger aria-label="Filter gudang" className="w-56">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua gudang</SelectItem>
                            {options.warehouses.map((row) => (
                                <SelectItem key={row.id} value={row.id}>
                                    {row.code} - {row.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {filters.tab !== 'openings' && (
                        <Select
                            value={filters.item_id || 'all'}
                            onValueChange={(id) => visit({ item_id: id === 'all' ? '' : id })}
                            disabled={loading}
                        >
                            <SelectTrigger aria-label="Filter barang" className="w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua barang</SelectItem>
                                {options.items.map((row) => (
                                    <SelectItem key={row.id} value={row.id}>
                                        {row.sku} - {row.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {filters.tab === 'openings' && (
                        <>
                            <Select
                                value={filters.status}
                                onValueChange={(status) => visit({ status: status as StockFilters['status'] })}
                                disabled={loading}
                            >
                                <SelectTrigger aria-label="Filter status" className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="posted">Posted</SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex rounded-md border">
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
                        </>
                    )}
                </div>
                <div className="relative min-w-0 overflow-x-auto rounded-md border bg-[var(--app-panel)]">
                    {loading && (
                        <div className="bg-background absolute top-3 right-3 p-1" role="status" aria-label="Memuat stok">
                            <LoaderCircle className="size-4 animate-spin" />
                        </div>
                    )}
                    <DataTable
                        rows={records.data}
                        tab={filters.tab}
                        canManage={canManage}
                        canPost={canPost}
                        onDetail={(record) => setDialog({ record, readOnly: true })}
                        onEdit={(record) => setDialog({ record, readOnly: false })}
                        onAction={(record, next) => setAction({ record, action: next })}
                    />
                </div>
                <div className="flex items-center justify-between gap-3 text-sm">
                    <span className="text-muted-foreground">
                        {records.meta.total} data - Halaman {records.meta.current_page} dari {records.meta.last_page}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            size="icon"
                            variant="outline"
                            aria-label="Halaman sebelumnya"
                            title="Halaman sebelumnya"
                            disabled={loading || records.meta.current_page <= 1}
                            onClick={() => visit({}, records.meta.current_page - 1)}
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <Button
                            size="icon"
                            variant="outline"
                            aria-label="Halaman berikutnya"
                            title="Halaman berikutnya"
                            disabled={loading || records.meta.current_page >= records.meta.last_page}
                            onClick={() => visit({}, records.meta.current_page + 1)}
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>
            </section>
            {dialog && (dialog.readOnly || canManage) && (
                <OpeningDialog record={dialog.record} options={options} readOnly={dialog.readOnly} onClose={() => setDialog(null)} />
            )}
            {action && (action.action === 'post' ? canPost : canManage) && <OpeningActionDialog {...action} onClose={() => setAction(null)} />}
        </AppLayout>
    );
}
