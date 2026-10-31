import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardCheck, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { StartDialog } from './components/start-dialog';
import { statusLabels, type CountIndexProps, type CountStatus } from './types';

export default function StockCounts({ records, filters, warehouses, operationKey }: CountIndexProps) {
    const { can } = usePermission();
    const [creating, setCreating] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const searchRef = useRef<HTMLInputElement>(null);
    const canManage = can('inventory.stock.counts.manage');
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
        const key = (e: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (e.altKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                searchRef.current?.focus();
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'n' && canManage) {
                e.preventDefault();
                setCreating(true);
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [canManage]);
    const visit = (patch: Partial<CountIndexProps['filters']>) =>
        router.get(
            route('inventory.stock.counts.index'),
            { ...tablePageParams(), ...filters, ...patch, page: 1 },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter opname tidak valid.') },
        );
    return (
        <AppLayout breadcrumbs={[{ title: 'Stock opname', href: route('inventory.stock.counts.index') }]}>
            <Head title="Stock opname" />
            <WorkspaceShortcutDropdown
                title="Stock opname"
                description={`${records.meta.total} sesi`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian sesi', keys: ['Alt', 'F'] },
                    { label: 'Mulai opname', description: 'Pilih gudang', keys: ['Ctrl/Cmd', 'Shift', 'N'], disabled: !canManage },
                    { label: 'Konfirmasi', description: 'Dialog aktif', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="flex items-center gap-2 text-xl font-semibold">
                        <ClipboardCheck className="text-teal-600 dark:text-teal-300" />
                        Stock opname
                    </h1>
                    {canManage && (
                        <Button disabled={loading} onClick={() => setCreating(true)}>
                            <Plus />
                            Mulai opname
                        </Button>
                    )}
                </div>
                <div className="workspace-filters flex flex-wrap gap-3">
                    <form
                        className="workspace-search flex min-w-0 flex-1 gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            aria-label="Cari opname"
                            ref={searchRef}
                            value={search}
                            maxLength={150}
                            placeholder="Nomor opname atau gudang..."
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button size="icon" variant="outline" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="text-sky-600" />
                        </Button>
                    </form>
                    <Select
                        value={filters.warehouse_id || 'all'}
                        onValueChange={(id) => visit({ warehouse_id: id === 'all' ? '' : id })}
                        disabled={loading}
                    >
                        <SelectTrigger aria-label="Filter gudang" className="w-56">
                            <SelectValue placeholder="Semua gudang" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua gudang</SelectItem>
                            {warehouses.map((row) => (
                                <SelectItem key={row.id} value={row.id}>
                                    {row.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.status || 'all'}
                        onValueChange={(status) => visit({ status: status === 'all' ? '' : status })}
                        disabled={loading}
                    >
                        <SelectTrigger aria-label="Filter status" className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            {Object.entries(statusLabels).map(([key, label]) => (
                                <SelectItem key={key} value={key}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="min-w-0 overflow-x-auto rounded-md border">
                    <Table className="workspace-table">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nomor opname</TableHead>
                                <TableHead>Gudang</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Operasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {records.data.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="font-mono text-xs">{row.number}</TableCell>
                                    <TableCell>{row.warehouse_name}</TableCell>
                                    <TableCell>
                                        <Badge variant={row.status === 'posted' ? 'default' : 'secondary'}>
                                            {statusLabels[row.status as CountStatus]}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={route('inventory.stock.counts.show', row.id)}>Buka sesi</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {records.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground py-10 text-center">
                                        Tidak ada sesi opname.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                <TablePagination meta={records.meta} disabled={loading} />
            </section>
            {creating && canManage && <StartDialog warehouses={warehouses} operationKey={operationKey} onClose={() => setCreating(false)} />}
        </AppLayout>
    );
}
