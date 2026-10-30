import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { History, LoaderCircle } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { DataTable } from './components/data-table';
import { DetailDialog } from './components/detail-dialog';
import { FilterBar } from './components/filter-bar';
import type { AuditFilters, AuditProps, AuditRow } from './types';

const empty: AuditFilters = { search: '', module: null, event: null, actor_id: null, date_from: null, date_to: null };
export default function AuditLog({ records, filters, options }: AuditProps) {
    const { can } = usePermission();
    const [draft, setDraft] = useState(filters);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [selected, setSelected] = useState<AuditRow | null>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    useEffect(() => setDraft(filters), [filters]);
    useEffect(() => {
        const start = router.on('start', () => {
            setSelected(null);
            setLoading(true);
        });
        const finish = router.on('finish', () => setLoading(false));
        return () => {
            start();
            finish();
        };
    }, []);
    const visit = useCallback((values: AuditFilters) => {
        setErrors({});
        router.get(
            route('platform.audit-log.index'),
            { ...tablePageParams(), ...values, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                onError: (issues) => {
                    setErrors(issues);
                    toast.error('Filter belum valid. Periksa isian.');
                },
            },
        );
    }, []);
    const apply = useCallback(() => {
        if (!loading) visit(draft);
    }, [draft, loading, visit]);
    useEffect(() => {
        const keydown = (event: KeyboardEvent) => {
            if (document.querySelector('[role=dialog]')) return;
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                searchRef.current?.focus();
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                apply();
            }
        };
        window.addEventListener('keydown', keydown);
        return () => window.removeEventListener('keydown', keydown);
    }, [apply]);
    return (
        <AppLayout breadcrumbs={[{ title: 'Log Aktivitas', href: route('platform.audit-log.index') }]}>
            <Head title="Log Aktivitas" />
            <WorkspaceShortcutDropdown
                title="Log Aktivitas"
                description={`${records.meta.total} aktivitas`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian aktivitas', keys: ['Alt', 'F'] },
                    { label: 'Terapkan filter', description: 'Perbarui daftar', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Tutup detail', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <History className="size-5 text-cyan-600 dark:text-cyan-300" />
                    Log Aktivitas
                </h1>
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
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat aktivitas...
                    </p>
                )}
                <DataTable rows={records.data} onOpen={setSelected} disabled={loading || !can('platform.audit-log.view')} />
                <TablePagination meta={records.meta} disabled={loading} />
            </section>
            <DetailDialog selected={selected} onClose={() => setSelected(null)} />
        </AppLayout>
    );
}
