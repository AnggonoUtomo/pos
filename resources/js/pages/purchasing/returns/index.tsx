import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, LoaderCircle, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { ReturnDataTable } from './components/data-table';
import { ReturnFormDialog } from './components/return-form-dialog';
import { ReturnLifecycleDialog } from './components/lifecycle-dialog';
import type { ReturnFilters, ReturnPageProps, ReturnRow } from './types';

export default function PurchaseReturns({ records, filters }: ReturnPageProps) {
    const { can } = usePermission();
    const canManage = can('purchasing.returns.manage');
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<{ record: ReturnRow | null; readOnly: boolean } | null>(null);
    const [lifecycle, setLifecycle] = useState<ReturnRow | null>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    useEffect(() => setSearch(filters.search), [filters.search]);
    useEffect(() => {
        const start = router.on('start', () => setLoading(true));
        const finish = router.on('finish', () => setLoading(false));
        return () => { start(); finish(); };
    }, []);
    useEffect(() => {
        const keydown = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                searchRef.current?.focus();
            }
            if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key.toLowerCase() === 'n' && canManage && !filters.trashed) {
                event.preventDefault();
                setDialog({ record: null, readOnly: false });
            }
        };
        window.addEventListener('keydown', keydown);
        return () => window.removeEventListener('keydown', keydown);
    }, [canManage, filters.trashed]);
    const visit = (patch: Partial<ReturnFilters>, page = 1) => router.get(route('purchasing.returns.index'), {
        ...filters, ...patch, trashed: (patch.trashed ?? filters.trashed) ? 1 : 0, page,
    }, { preserveState: true, preserveScroll: true, onError: () => toast.error('Data retur belum dapat dimuat. Periksa filter dan akses.') });
    return <AppLayout breadcrumbs={[{ title: 'Pembelian', href: route('purchasing.purchase-orders.index') }, { title: 'Retur pembelian', href: route('purchasing.returns.index') }]}>
        <Head title="Retur pembelian" />
        <WorkspaceShortcutDropdown title="Retur pembelian" description={`${records.meta.total} draft ${filters.trashed ? 'terhapus' : 'tersedia'}`} shortcuts={[
            { label: 'Cari', description: 'Fokus pencarian draft', keys: ['Alt', 'F'] },
            { label: 'Tambah', description: 'Buat draft dari pembelian diterima', keys: ['Ctrl/Cmd', 'Shift', 'N'], disabled: !canManage || filters.trashed },
            { label: 'Simpan', description: 'Simpan dialog aktif', keys: ['Ctrl/Cmd', 'Enter'], disabled: !canManage },
            { label: 'Tutup', description: 'Tutup dialog saat tidak sedang menyimpan', keys: ['Esc'] },
        ]} />
        <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div><h1 className="text-xl font-semibold">Retur pembelian</h1><p className="text-muted-foreground text-sm">Pengelolaan draft dari pembelian yang sudah diterima.</p></div>
                {canManage && !filters.trashed && <Button disabled={loading} onClick={() => setDialog({ record: null, readOnly: false })}><Plus className="size-4" />Tambah draft retur</Button>}
            </div>
            <div className="flex flex-wrap gap-3">
                <form className="flex min-w-0 flex-1 gap-2 sm:min-w-64" onSubmit={(event) => { event.preventDefault(); visit({ search }); }}>
                    <Input ref={searchRef} id="return-search" name="search" aria-label="Cari draft retur" maxLength={150} placeholder="Nomor retur, pembelian, atau supplier" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={loading}><Search className="size-4 text-sky-600 dark:text-sky-300" /></Button>
                </form>
                <Select value={filters.status} onValueChange={(status: ReturnFilters['status']) => visit({ status })} disabled={loading}>
                    <SelectTrigger className="w-44" aria-label="Filter status retur"><SelectValue /></SelectTrigger>
                    <SelectContent><SelectItem value="all">Semua status</SelectItem><SelectItem value="draft">Draft</SelectItem></SelectContent>
                </Select>
                <div className="flex rounded-md border p-1" role="group" aria-label="Data retur">
                    {[false, true].map((trashed) => <Button key={String(trashed)} variant={filters.trashed === trashed ? 'secondary' : 'ghost'} aria-pressed={filters.trashed === trashed} disabled={loading} onClick={() => visit({ trashed })}>{trashed ? 'Terhapus' : 'Data aktif'}</Button>)}
                </div>
            </div>
            {loading && <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm"><LoaderCircle className="size-4 animate-spin text-sky-600" />Memuat draft retur...</p>}
            <ReturnDataTable rows={records.data} trashed={filters.trashed} canManage={canManage} onOpen={(record, readOnly) => setDialog({ record, readOnly })} onLifecycle={setLifecycle} />
            <div className="text-muted-foreground flex flex-wrap items-center justify-between gap-3 text-sm">
                <span>{records.meta.total} draft · Halaman {records.meta.current_page} dari {records.meta.last_page}</span>
                <div className="flex gap-2">
                    <Button variant="outline" size="icon" aria-label="Halaman sebelumnya" title="Halaman sebelumnya" disabled={loading || records.meta.current_page <= 1} onClick={() => visit({}, records.meta.current_page - 1)}><ChevronLeft className="size-4" /></Button>
                    <Button variant="outline" size="icon" aria-label="Halaman berikutnya" title="Halaman berikutnya" disabled={loading || records.meta.current_page >= records.meta.last_page} onClick={() => visit({}, records.meta.current_page + 1)}><ChevronRight className="size-4" /></Button>
                </div>
            </div>
        </section>
        {dialog && (dialog.readOnly || canManage) && <ReturnFormDialog key={`${dialog.record?.id ?? 'new'}-${dialog.readOnly}`} record={dialog.record} readOnly={dialog.readOnly} onClose={() => setDialog(null)} />}
        {canManage && lifecycle && <ReturnLifecycleDialog key={lifecycle.id} record={lifecycle} onClose={() => setLifecycle(null)} />}
    </AppLayout>;
}
