import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ArrowRightLeft, ChevronLeft, ChevronRight, LoaderCircle, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { TransferDataTable } from './components/data-table';
import { LifecycleDialog } from './components/lifecycle-dialog';
import { TransferFormDialog } from './components/transfer-form-dialog';
import type { TransferFilters, TransferPageProps, TransferRow } from './types';

export default function Transfers({ records, filters, options }: TransferPageProps) {
    const { can } = usePermission();
    const canManage = can('inventory.transfers.manage');
    const canPost = can('inventory.transfers.post');
    const [posting, setPosting] = useState<TransferRow | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<{ record: TransferRow | null; readOnly: boolean } | null>(null);
    const [lifecycle, setLifecycle] = useState<TransferRow | null>(null);
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
    const visit = (patch: Partial<TransferFilters>, page = 1) =>
        router.get(
            route('inventory.transfers.index'),
            { ...filters, ...patch, trashed: (patch.trashed ?? filters.trashed) ? 1 : 0, page },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Data transfer belum dapat dimuat.') },
        );
    return (
        <AppLayout breadcrumbs={[{ title: 'Transfer antar-gudang', href: route('inventory.transfers.index') }]}>
            <Head title="Transfer antar-gudang" />
            <WorkspaceShortcutDropdown
                title="Transfer antar-gudang"
                description={`${records.meta.total} transfer ${filters.trashed ? 'terhapus' : 'tersedia'}`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    {
                        label: 'Tambah',
                        description: 'Buat draft transfer',
                        keys: ['Ctrl/Cmd', 'Shift', 'N'],
                        disabled: !canManage || filters.trashed,
                    },
                    {
                        label: 'Simpan/konfirmasi',
                        description: 'Konfirmasi dialog aktif',
                        keys: ['Ctrl/Cmd', 'Enter'],
                        disabled: !canManage && !canPost,
                    },
                    { label: 'Tutup', description: 'Tutup dialog saat tidak memproses', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="flex items-center gap-2 text-xl font-semibold">
                        <ArrowRightLeft className="size-5 text-cyan-600 dark:text-cyan-300" />
                        Transfer antar-gudang
                    </h1>
                    {canManage && !filters.trashed && (
                        <Button disabled={loading} onClick={() => setDialog({ record: null, readOnly: false })}>
                            <Plus className="size-4" />
                            Tambah draft transfer
                        </Button>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <form
                        className="flex min-w-0 flex-1 gap-2 sm:min-w-64"
                        onSubmit={(e) => {
                            e.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            ref={searchRef}
                            aria-label="Cari transfer"
                            placeholder="Nomor transfer atau gudang"
                            maxLength={150}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </form>
                    {(['source', 'destination'] as const).map((prefix) => {
                        const key = `${prefix}_warehouse_id` as const;
                        const label = prefix === 'source' ? 'Gudang asal' : 'Gudang tujuan';
                        return (
                            <Select
                                key={prefix}
                                value={filters[key] || 'all'}
                                disabled={loading}
                                onValueChange={(id) => visit({ [key]: id === 'all' ? '' : id })}
                            >
                                <SelectTrigger className="w-48" aria-label={`Filter ${label.toLowerCase()}`}>
                                    <SelectValue placeholder={label} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua {label.toLowerCase()}</SelectItem>
                                    {options.warehouses.map((row) => (
                                        <SelectItem key={row.id} value={row.id}>
                                            {row.code} - {row.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        );
                    })}
                    <div className="flex rounded-md border p-1" role="group" aria-label="Data transfer">
                        {[false, true].map((trashed) => (
                            <Button
                                key={String(trashed)}
                                variant={filters.trashed === trashed ? 'secondary' : 'ghost'}
                                aria-pressed={filters.trashed === trashed}
                                disabled={loading}
                                onClick={() => visit({ trashed })}
                            >
                                {trashed ? 'Terhapus' : 'Data aktif'}
                            </Button>
                        ))}
                    </div>
                </div>
                {loading && (
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat transfer...
                    </p>
                )}
                <TransferDataTable
                    rows={records.data}
                    canManage={canManage}
                    canPost={canPost}
                    onPost={setPosting}
                    onOpen={(record, readOnly) => setDialog({ record, readOnly })}
                    onLifecycle={setLifecycle}
                />
                <div className="text-muted-foreground flex flex-wrap items-center justify-between gap-3 text-sm">
                    <span>
                        {records.meta.total} transfer / Halaman {records.meta.current_page} dari {records.meta.last_page}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Halaman sebelumnya"
                            title="Halaman sebelumnya"
                            disabled={loading || records.meta.current_page <= 1}
                            onClick={() => visit({}, records.meta.current_page - 1)}
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
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
                <TransferFormDialog
                    key={`${dialog.record?.id ?? 'new'}-${dialog.readOnly}`}
                    record={dialog.record}
                    options={options}
                    readOnly={dialog.readOnly}
                    onClose={() => setDialog(null)}
                />
            )}
            {canManage && lifecycle && <LifecycleDialog key={lifecycle.id} record={lifecycle} onClose={() => setLifecycle(null)} />}
            {canPost && posting && <LifecycleDialog key={`post-${posting.id}`} record={posting} posting onClose={() => setPosting(null)} />}
        </AppLayout>
    );
}
