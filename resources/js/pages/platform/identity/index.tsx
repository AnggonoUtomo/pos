import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, KeyRound, LoaderCircle, Plus, Search, ShieldCheck, Users } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { AccessEditorDialog } from './components/access-editor-dialog';
import { AccessTable } from './components/access-table';
import { RoleFormDialog } from './components/role-form-dialog';
import type { AccessEditor, AccessPageProps, AccessTab, RoleRow } from './types';

const tabs = [
    { key: 'roles', label: 'Role', icon: ShieldCheck, tone: 'text-violet-600 dark:text-violet-300' },
    { key: 'permissions', label: 'Permission', icon: KeyRound, tone: 'text-amber-600 dark:text-amber-300' },
    { key: 'users', label: 'Akses Pengguna', icon: Users, tone: 'text-sky-600 dark:text-sky-300' },
] as const;

export default function IdentityIndex({ records, filters, options }: AccessPageProps) {
    const { can, isSuperSystem } = usePermission();
    const canManage = can('platform.identity.manage');
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [editor, setEditor] = useState<AccessEditor | null>(null);
    const [deleting, setDeleting] = useState<RoleRow | null>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const { meta } = records;
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
            if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key.toLowerCase() === 'n' && canManage && filters.tab === 'roles') {
                event.preventDefault();
                setEditor({ kind: 'role', record: null });
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [canManage, filters.tab]);
    const visit = (tab: AccessTab, query = filters.search, page = 1) =>
        router.get(
            route('platform.identity.index'),
            { tab, search: query, page },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter akses tidak valid.') },
        );
    return (
        <AppLayout breadcrumbs={[{ title: 'Identitas & Akses', href: route('platform.identity.index') }]}>
            <Head title="Identitas & Akses" />
            <WorkspaceShortcutDropdown
                title="Identitas & Akses"
                description={`${meta.total} data tersedia`}
                shortcuts={[
                    { label: 'Cari', description: 'Fokus pencarian', keys: ['Alt', 'F'] },
                    {
                        label: 'Tambah role',
                        description: 'Role baru',
                        keys: ['Ctrl/Cmd', 'Shift', 'N'],
                        disabled: !canManage || filters.tab !== 'roles',
                    },
                    { label: 'Simpan', description: 'Simpan form', keys: ['Ctrl/Cmd', 'Enter'], disabled: !canManage },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={loading}>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Identitas & Akses</h1>
                    {canManage && filters.tab === 'roles' && (
                        <Button disabled={loading} onClick={() => setEditor({ kind: 'role', record: null })}>
                            <Plus className="size-4" />
                            Tambah role
                        </Button>
                    )}
                </div>
                <div
                    className="flex flex-wrap gap-1 border-b"
                    role="tablist"
                    aria-label="Manajemen akses"
                    onKeyDown={(event) => {
                        const index = tabs.findIndex((tab) => tab.key === filters.tab);
                        const next =
                            event.key === 'ArrowRight'
                                ? (index + 1) % tabs.length
                                : event.key === 'ArrowLeft'
                                  ? (index + tabs.length - 1) % tabs.length
                                  : event.key === 'Home'
                                    ? 0
                                    : event.key === 'End'
                                      ? tabs.length - 1
                                      : -1;
                        if (next >= 0) {
                            event.preventDefault();
                            (event.currentTarget.children[next] as HTMLElement).focus();
                            visit(tabs[next].key, '');
                        }
                    }}
                >
                    {tabs.map((tab) => (
                        <Button
                            key={tab.key}
                            id={`access-tab-${tab.key}`}
                            role="tab"
                            variant="ghost"
                            aria-selected={filters.tab === tab.key}
                            aria-controls="access-panel"
                            tabIndex={filters.tab === tab.key ? 0 : -1}
                            className={`rounded-b-none border-b-2 ${filters.tab === tab.key ? 'border-primary bg-muted/50' : 'border-transparent'}`}
                            disabled={loading}
                            onClick={() => visit(tab.key, '')}
                        >
                            <tab.icon className={`size-4 ${tab.tone}`} />
                            {tab.label}
                        </Button>
                    ))}
                </div>
                <div id="access-panel" role="tabpanel" aria-labelledby={`access-tab-${filters.tab}`} className="space-y-4">
                    <form
                        className="flex max-w-xl gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            visit(filters.tab, search);
                        }}
                    >
                        <Input
                            ref={searchRef}
                            aria-label="Cari akses"
                            placeholder={filters.tab === 'users' ? 'Cari nama atau email...' : 'Cari nama...'}
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            maxLength={150}
                        />
                        <Button variant="outline" size="icon" aria-label="Cari" title="Cari" disabled={loading}>
                            <Search className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </form>
                    <div className="relative min-w-0 overflow-x-auto rounded-md border bg-[var(--app-panel)]">
                        {loading && (
                            <div className="bg-background absolute top-3 right-3 z-10 rounded p-1" role="status" aria-label="Memuat akses">
                                <LoaderCircle className="size-4 animate-spin" />
                            </div>
                        )}
                        <AccessTable
                            rows={records.data}
                            tab={filters.tab}
                            canManage={canManage}
                            isSuperSystem={isSuperSystem}
                            canGrant={can}
                            onEdit={setEditor}
                            onDelete={setDeleting}
                        />
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
                                onClick={() => visit(filters.tab, filters.search, meta.current_page - 1)}
                            >
                                <ChevronLeft className="size-4 text-sky-600 dark:text-sky-300" />
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                aria-label="Halaman berikutnya"
                                title="Halaman berikutnya"
                                disabled={loading || meta.current_page >= meta.last_page}
                                onClick={() => visit(filters.tab, filters.search, meta.current_page + 1)}
                            >
                                <ChevronRight className="size-4 text-sky-600 dark:text-sky-300" />
                            </Button>
                        </div>
                    </div>
                </div>
                {canManage && editor?.kind === 'role' && (
                    <RoleFormDialog key={editor.record?.id ?? 'new'} record={editor.record} onClose={() => setEditor(null)} />
                )}
                {canManage && editor && editor.kind !== 'role' && (
                    <AccessEditorDialog
                        key={`${editor.kind}-${editor.record.id}`}
                        editor={editor}
                        options={options}
                        onClose={() => setEditor(null)}
                    />
                )}
                {canManage && deleting && (
                    <RoleFormDialog key={`delete-${deleting.id}`} record={deleting} deleting onClose={() => setDeleting(null)} />
                )}
            </section>
        </AppLayout>
    );
}
