import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { formatDecimalInput } from '@/lib/decimal-input';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, PackageSearch, Pencil, Plus, RotateCcw, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { MinimumDialog } from './components/minimum-dialog';
import { statusLabels, type MinimumProps, type MinimumRow } from './types';

export default function MinimumStock({ records, filters, options }: MinimumProps) {
    const { can } = usePermission();
    const canManage = can('inventory.stock.minimums.manage');
    const [editor, setEditor] = useState<{ row: MinimumRow | null } | null>(null);
    const [confirm, setConfirm] = useState<MinimumRow | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [busy, setBusy] = useState(false);
    const searchRef = useRef<HTMLInputElement>(null);
    useEffect(() => setSearch(filters.search), [filters.search]);
    useEffect(() => {
        const start = router.on('start', () => setBusy(true));
        const end = router.on('finish', () => setBusy(false));
        return () => {
            start();
            end();
        };
    }, []);
    useEffect(() => {
        const key = (e: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (e.altKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                searchRef.current?.focus();
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'n' && canManage) {
                e.preventDefault();
                setEditor({ row: null });
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [canManage]);
    const visit = (patch: Partial<MinimumProps['filters']>) =>
        router.get(
            route('inventory.stock.minimums.index'),
            { ...tablePageParams(), ...filters, ...patch, trashed: Number(patch.trashed ?? filters.trashed), page: 1 },
            { preserveScroll: true, preserveState: true, onError: () => toast.error('Filter belum dapat dimuat.') },
        );
    const remove = () => {
        if (!confirm || busy) return;
        const callbacks = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(confirm.deleted_at ? 'Pengaturan dipulihkan.' : 'Pengaturan dihapus.');
                setConfirm(null);
            },
            onError: (errors: Record<string, string>) => toast.error(Object.values(errors)[0] ?? 'Operasi gagal. Muat ulang data.'),
        };
        if (confirm.deleted_at) router.post(route('inventory.stock.minimums.restore', confirm.id), { version: confirm.version }, callbacks);
        else router.delete(route('inventory.stock.minimums.destroy', confirm.id), { ...callbacks, data: { version: confirm.version } });
    };
    return (
        <AppLayout breadcrumbs={[{ title: 'Stok minimum', href: route('inventory.stock.minimums.index') }]}>
            <Head title="Stok minimum" />
            <WorkspaceShortcutDropdown
                title="Stok minimum"
                description={`${records.meta.total} pengaturan`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    { label: 'Tambah', description: 'Atur batas barang/gudang', keys: ['Ctrl/Cmd', 'Shift', 'N'], disabled: !canManage },
                    { label: 'Konfirmasi', description: 'Dialog aktif', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={busy}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="flex items-center gap-2 text-xl font-semibold">
                        <PackageSearch className="text-amber-600 dark:text-amber-300" />
                        Stok minimum
                    </h1>
                    {canManage && (
                        <Button disabled={busy} onClick={() => setEditor({ row: null })}>
                            <Plus />
                            Tambah batas
                        </Button>
                    )}
                </div>
                {!filters.trashed && (
                    <div className="flex flex-wrap items-center gap-3 border-y border-amber-500/20 bg-amber-500/5 p-4 text-sm" role="status">
                        <AlertTriangle className="size-5 text-amber-600 dark:text-amber-300" />
                        <span>
                            <strong>{records.summary.low_count.toLocaleString('id-ID')}</strong> perlu diperiksa ·{' '}
                            <strong>{records.summary.empty_count.toLocaleString('id-ID')}</strong> habis
                        </span>
                    </div>
                )}
                <div className="workspace-filters flex flex-wrap gap-3">
                    <form
                        className="workspace-search flex w-full min-w-0 flex-none gap-2 xl:w-auto xl:flex-1"
                        onSubmit={(e) => {
                            e.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            ref={searchRef}
                            aria-label="Cari stok minimum"
                            value={search}
                            maxLength={150}
                            placeholder="SKU, nama barang atau gudang..."
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={busy}>
                            <Search className="text-sky-600" />
                        </Button>
                    </form>
                    <Select
                        value={filters.warehouse_id || 'all'}
                        onValueChange={(id) => visit({ warehouse_id: id === 'all' ? '' : id })}
                        disabled={busy}
                    >
                        <SelectTrigger aria-label="Filter gudang" className="w-52">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua gudang</SelectItem>
                            {options.warehouses.map((w) => (
                                <SelectItem key={w.id} value={w.id}>
                                    {w.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.status}
                        onValueChange={(status) => visit({ status: status as MinimumProps['filters']['status'] })}
                        disabled={busy}
                    >
                        <SelectTrigger aria-label="Filter status" className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(statusLabels).map(([key, label]) => (
                                <SelectItem key={key} value={key}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.trashed ? 'deleted' : 'current'}
                        onValueChange={(value) => visit({ trashed: value === 'deleted' })}
                        disabled={busy}
                    >
                        <SelectTrigger aria-label="Filter arsip" className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="current">Belum dihapus</SelectItem>
                            <SelectItem value="deleted">Terhapus</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="min-w-0 overflow-x-auto rounded-md border">
                    <Table className="workspace-table">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Barang</TableHead>
                                <TableHead>Gudang</TableHead>
                                <TableHead className="text-right">Saldo</TableHead>
                                <TableHead className="text-right">Minimum</TableHead>
                                <TableHead className="text-right">Selisih ke batas</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Operasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {records.data.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell>
                                        <span className="font-medium">{row.item_name}</span>
                                        <span className="text-muted-foreground block text-xs">
                                            {row.sku} · {row.unit_name}
                                        </span>
                                    </TableCell>
                                    <TableCell>{row.warehouse_name}</TableCell>
                                    <TableCell className="text-right tabular-nums">{formatDecimalInput(row.quantity)}</TableCell>
                                    <TableCell className="text-right tabular-nums">{formatDecimalInput(row.minimum_quantity)}</TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {row.status === 'low' ? formatDecimalInput(row.shortage) : '-'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant="secondary"
                                            className={
                                                row.status === 'low'
                                                    ? 'bg-amber-500/15 text-amber-800 dark:text-amber-200'
                                                    : row.status === 'healthy'
                                                      ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-200'
                                                      : ''
                                            }
                                        >
                                            {row.deleted_at ? 'Terhapus' : statusLabels[row.status]}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-1">
                                            {canManage && (
                                                <>
                                                    {!row.deleted_at && row.eligible && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title={`Ubah ${row.sku}`}
                                                            aria-label={`Ubah ${row.sku}`}
                                                            disabled={busy}
                                                            onClick={() => setEditor({ row })}
                                                        >
                                                            <Pencil className="size-4 text-sky-600 dark:text-sky-300" />
                                                        </Button>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title={`${row.deleted_at ? 'Pulihkan' : 'Hapus'} ${row.sku}`}
                                                        aria-label={`${row.deleted_at ? 'Pulihkan' : 'Hapus'} ${row.sku}`}
                                                        disabled={busy || (!!row.deleted_at && !row.eligible)}
                                                        onClick={() => setConfirm(row)}
                                                    >
                                                        {row.deleted_at ? (
                                                            <RotateCcw className="size-4 text-emerald-600 dark:text-emerald-300" />
                                                        ) : (
                                                            <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                                                        )}
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {records.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-muted-foreground py-10 text-center">
                                        Tidak ada pengaturan yang sesuai.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                <TablePagination meta={records.meta} disabled={busy} />
            </section>
            {editor && canManage && <MinimumDialog row={editor.row} options={options} onClose={() => setEditor(null)} />}
            {confirm && (
                <Dialog
                    open
                    onOpenChange={(open) => {
                        if (!open && !busy) setConfirm(null);
                    }}
                >
                    <DialogContent
                        onKeyDown={(e) => {
                            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                                e.preventDefault();
                                remove();
                            }
                        }}
                    >
                        <DialogHeader>
                            <DialogTitle>{confirm.deleted_at ? 'Pulihkan' : 'Hapus'} stok minimum?</DialogTitle>
                            <DialogDescription>
                                {confirm.item_name} / {confirm.warehouse_name}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" disabled={busy} onClick={() => setConfirm(null)}>
                                Batal
                            </Button>
                            <Button variant={confirm.deleted_at ? 'default' : 'destructive'} disabled={busy} onClick={remove}>
                                {confirm.deleted_at ? <RotateCcw /> : <Trash2 />}
                                {confirm.deleted_at ? 'Pulihkan' : 'Hapus'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}
        </AppLayout>
    );
}
