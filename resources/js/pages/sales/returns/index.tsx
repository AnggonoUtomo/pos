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
import { LifecycleDialog } from './components/lifecycle-dialog';
import { RefundDialog } from './components/refund-dialog';
import { ReturnFormDialog } from './components/return-form-dialog';
import type { ReturnFilters, ReturnPageProps, ReturnRow } from './types';

export default function Returns({ records, filters }: ReturnPageProps) {
    const { can } = usePermission();
    const canManage = can('sales.returns.manage');
    const canPost = can('sales.returns.post');
    const canRefund = can('sales.returns.refund');
    const [refund, setRefund] = useState<ReturnRow | null>(null);
    const [posting, setPosting] = useState<ReturnRow | null>(null);
    const [dialog, setDialog] = useState<{ record: ReturnRow | null; readOnly: boolean } | null>(null);
    const [lifecycle, setLifecycle] = useState<ReturnRow | null>(null);
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
    const visit = (patch: Partial<ReturnFilters>, page = 1) =>
        router.get(
            route('sales.returns.index'),
            { ...filters, ...patch, trashed: (patch.trashed ?? filters.trashed) ? 1 : 0, page },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter belum valid.') },
        );
    return (
        <AppLayout breadcrumbs={[{ title: 'Retur penjualan', href: route('sales.returns.index') }]}>
            <Head title="Retur penjualan" />
            <WorkspaceShortcutDropdown
                title="Retur penjualan"
                description={`${records.meta.total} dokumen ${filters.trashed ? 'terhapus' : 'tersedia'}`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    { label: 'Tambah', description: 'Tambah draft retur', keys: ['Ctrl/Cmd', 'Shift', 'N'], disabled: !canManage || filters.trashed },
                    {
                        label: 'Simpan / Konfirmasi',
                        description: 'Operasi dialog aktif',
                        keys: ['Ctrl/Cmd', 'Enter'],
                        disabled: !canManage && !canPost && !canRefund,
                    },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Retur penjualan</h1>
                    {canManage && !filters.trashed && (
                        <Button disabled={loading} onClick={() => setDialog({ record: null, readOnly: false })}>
                            <Plus className="size-4" />
                            Tambah draft retur
                        </Button>
                    )}
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <form
                        className="flex min-w-0 flex-1 gap-2 sm:min-w-64"
                        onSubmit={(e) => {
                            e.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            ref={searchRef}
                            aria-label="Cari retur"
                            placeholder="Nomor retur, invoice, pelanggan..."
                            value={search}
                            maxLength={150}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </form>
                    <Select value={filters.status} disabled={loading} onValueChange={(status: ReturnFilters['status']) => visit({ status })}>
                        <SelectTrigger className="w-44" aria-label="Status retur">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="posted">Diterima</SelectItem>
                        </SelectContent>
                    </Select>
                    <div className="flex rounded-md border p-1" role="group" aria-label="Tampilan retur">
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
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Memuat...
                    </p>
                )}
                <DataTable
                    rows={records.data}
                    canManage={canManage && !loading}
                    canPost={canPost && !loading}
                    canRefund={canRefund && !loading}
                    onRefund={setRefund}
                    onPost={setPosting}
                    onOpen={(record, readOnly) => setDialog({ record, readOnly: readOnly || record.status === 'posted' })}
                    onLifecycle={setLifecycle}
                />
                <div className="text-muted-foreground flex items-center justify-between gap-3 text-sm">
                    <span>
                        Halaman {records.meta.current_page} dari {records.meta.last_page} / {records.meta.total} dokumen
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
            {dialog && <ReturnFormDialog record={dialog.record} readOnly={dialog.readOnly} onClose={() => setDialog(null)} />}
            {lifecycle && <LifecycleDialog record={lifecycle} onClose={() => setLifecycle(null)} />}
            {posting && <LifecycleDialog record={posting} mode="post" onClose={() => setPosting(null)} />}
            {refund && (
                <RefundDialog
                    record={refund}
                    onClose={() => {
                        setRefund(null);
                        router.reload({ only: ['records'] });
                    }}
                    onSettled={() => router.reload({ only: ['records'] })}
                />
            )}
        </AppLayout>
    );
}
