import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown, type WorkspaceShortcut } from '@/components/workspace-shortcuts';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, LoaderCircle, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';

export interface MasterFilters {
    search: string;
    status: 'all' | 'active' | 'inactive';
    trashed: boolean;
}
export interface MasterRecord {
    id: string;
    name: string;
    is_active: boolean;
    deleted_at?: string | null;
}
export interface MasterRecords<T> {
    data: T[];
    meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export function MasterWorkspace({
    title,
    module,
    filters,
    meta,
    canManage,
    onCreate,
    searchPlaceholder = 'Cari kode atau nama...',
    routePrefix = `inventory.${module}`,
    areaTitle = 'Inventori',
    controls,
    extraShortcuts = [],
    children,
}: {
    title: string;
    module: string;
    filters: MasterFilters;
    meta: MasterRecords<unknown>['meta'];
    canManage: boolean;
    onCreate: () => void;
    searchPlaceholder?: string;
    routePrefix?: string;
    areaTitle?: string;
    controls?: ReactNode;
    extraShortcuts?: WorkspaceShortcut[];
    children: ReactNode;
}) {
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
        const handler = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                searchRef.current?.focus();
            }
            if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key.toLowerCase() === 'n' && canManage && !filters.trashed) {
                event.preventDefault();
                onCreate();
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [canManage, filters.trashed, onCreate]);
    const visit = (patch: Partial<MasterFilters>, page = 1) =>
        router.get(
            route(`${routePrefix}.index`),
            {
                ...filters,
                ...patch,
                trashed: (patch.trashed ?? filters.trashed) ? 1 : 0,
                page,
            },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter tidak valid. Silakan periksa kembali.') },
        );

    return (
        <AppLayout
            breadcrumbs={[
                { title: areaTitle, href: route(`${routePrefix}.index`) },
                { title, href: route(`${routePrefix}.index`) },
            ]}
        >
            <Head title={title} />
            <WorkspaceShortcutDropdown
                title={title}
                description={`${meta.total} data ${filters.trashed ? 'terhapus' : 'tersedia'}`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    {
                        label: 'Tambah',
                        description: `Tambah ${title.toLowerCase()}`,
                        keys: ['Ctrl/Cmd', 'Shift', 'N'],
                        disabled: !canManage || filters.trashed,
                    },
                    { label: 'Simpan', description: 'Simpan form yang sedang dibuka', keys: ['Ctrl/Cmd', 'Enter'], disabled: !canManage },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                    ...extraShortcuts,
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">{title}</h1>
                    {canManage && !filters.trashed && (
                        <Button onClick={onCreate} disabled={loading}>
                            <Plus className="size-4" />
                            Tambah {title.toLowerCase()}
                        </Button>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <form
                        className="flex min-w-0 flex-1 gap-2 sm:min-w-64"
                        onSubmit={(event) => {
                            event.preventDefault();
                            visit({ search });
                        }}
                    >
                        <Input
                            ref={searchRef}
                            aria-label={`Cari ${title.toLowerCase()}`}
                            placeholder={searchPlaceholder}
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            maxLength={150}
                        />
                        <Button variant="outline" size="icon" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </form>
                    <Select value={filters.status} onValueChange={(status: MasterFilters['status']) => visit({ status })} disabled={loading}>
                        <SelectTrigger className="w-40" aria-label="Filter status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            <SelectItem value="active">Aktif</SelectItem>
                            <SelectItem value="inactive">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>
                    <div className="flex rounded-md border" aria-label="Status penghapusan">
                        <Button
                            variant={!filters.trashed ? 'secondary' : 'ghost'}
                            className="rounded-r-none"
                            disabled={loading}
                            aria-pressed={!filters.trashed}
                            onClick={() => visit({ trashed: false })}
                        >
                            Data
                        </Button>
                        <Button
                            variant={filters.trashed ? 'secondary' : 'ghost'}
                            className="rounded-l-none"
                            disabled={loading}
                            aria-pressed={filters.trashed}
                            onClick={() => visit({ trashed: true })}
                        >
                            Terhapus
                        </Button>
                    </div>
                </div>
                {controls}
                <div className="relative min-w-0 overflow-x-auto rounded-md border bg-[var(--app-panel)]">
                    {loading && (
                        <div className="bg-background absolute top-3 right-3 z-10 rounded p-1" role="status" aria-label="Memuat data">
                            <LoaderCircle className="size-4 animate-spin" />
                        </div>
                    )}
                    {children}
                </div>
                <div className="flex items-center justify-between gap-3 text-sm">
                    <span className="text-muted-foreground">
                        {meta.total} data - Halaman {meta.current_page} dari {meta.last_page}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Halaman sebelumnya"
                            title="Halaman sebelumnya"
                            disabled={loading || meta.current_page <= 1}
                            onClick={() => visit({}, meta.current_page - 1)}
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Halaman berikutnya"
                            title="Halaman berikutnya"
                            disabled={loading || meta.current_page >= meta.last_page}
                            onClick={() => visit({}, meta.current_page + 1)}
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>
            </section>
        </AppLayout>
    );
}
