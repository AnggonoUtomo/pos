import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { TableMeta } from '@/components/shared/table-pagination-model';
import { Head, router } from '@inertiajs/react';
import { ArchiveRestore, History, Pencil, Plus, Power, Printer, ScanBarcode, Search, Settings, Tag, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { GatewayPanel } from './components/gateway-panel';
import { LabelComposer } from './components/label-composer';
import { PrintJobTable, jobStatusLabels } from './components/print-job-table';
import { PrinterProfileForm } from './components/printer-profile-form';
import { ScannerTestPanel } from './components/scanner-test-panel';
import { hardwareRequest, useLabelPrint } from './hooks/use-label-print';
import { useQzGateway } from './hooks/use-qz-gateway';
import type { CatalogLabel, LabelJob, PrinterProfile } from './types';

const tabs = [{ id: 'printers', name: 'Printer', icon: Settings, tone: 'text-cyan-600 dark:text-cyan-300' },
    { id: 'labels', name: 'Label', icon: Tag, tone: 'text-emerald-600 dark:text-emerald-300' },
    { id: 'history', name: 'Riwayat', icon: History, tone: 'text-amber-600 dark:text-amber-300' },
    { id: 'scanner', name: 'Scanner', icon: ScanBarcode, tone: 'text-violet-600 dark:text-violet-300' }];

export default function HardwareWorkspace({ profiles, allowedHosts, printingEnabled, history, filters }: {
    profiles: PrinterProfile[]; allowedHosts: string[]; printingEnabled: boolean;
    history: { data: LabelJob[]; meta: TableMeta }; filters: { q?: string; status?: string };
}) {
    const { can, user } = usePermission();
    const manage = can('platform.hardware.manage');
    const print = can('platform.hardware.print');
    const [tab, setTab] = useState('printers');
    const [editor, setEditor] = useState<{ profile: PrinterProfile | null } | null>(null);
    const [confirmDelete, setConfirmDelete] = useState<PrinterProfile | null>(null);
    const [selected, setSelected] = useState<CatalogLabel | null>(null);
    const [profileId, setProfileId] = useState('');
    const [search, setSearch] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [mutating, setMutating] = useState(false);
    const connection = useQzGateway(allowedHosts, manage || print);
    const changed = () => router.reload({ only: ['profiles', 'history'] });
    const printing = useLabelPrint(connection.gateway, changed);
    const creation = useRef({ fingerprint: '', key: '' });
    const busy = printing.busy || mutating;
    useEffect(() => {
        const keydown = (event: KeyboardEvent) => {
            if (!event.altKey || event.ctrlKey || event.metaKey || document.querySelector('[role="dialog"]')) return;
            const next = Number(event.key) - 1;
            if (next >= 0 && next < tabs.length && !busy) { event.preventDefault(); setTab(tabs[next].id); }
            if (event.key.toLowerCase() === 'f') { event.preventDefault(); setTab('history'); requestAnimationFrame(() => document.getElementById('hardware-history-search')?.focus()); }
        };
        window.addEventListener('keydown', keydown);
        return () => window.removeEventListener('keydown', keydown);
    }, [busy]);
    const create = (body: Record<string, unknown>) => {
        const fingerprint = JSON.stringify(body);
        if (creation.current.fingerprint !== fingerprint) creation.current = { fingerprint, key: crypto.randomUUID() };
        return hardwareRequest<{ job: LabelJob }>('/platform/hardware/labels', 'POST', { ...body, idempotency_key: creation.current.key }).then((value) => value.job);
    };
    const send = (label: CatalogLabel, profile: PrinterProfile, copies: number) => {
        void runCreated({ kind: 'product_label', printer_id: profile.id, item_id: label.item_id, unit_id: label.unit_id, copies });
    };
    const runCreated = async (body: Record<string, unknown>) => {
        const result = await printing.run(() => create(body));
        if (result?.status === 'submitted') creation.current = { fingerprint: '', key: '' };
    };
    const profileCommand = async (profile: PrinterProfile, operation: 'delete' | 'restore' | 'status') => {
        if (busy) return;
        setMutating(true);
        try {
            const suffix = operation === 'delete' ? '' : `/${operation}`;
            await hardwareRequest(`/platform/hardware/printers/${profile.id}${suffix}`, operation === 'delete' ? 'DELETE' : operation === 'restore' ? 'POST' : 'PATCH', {
                version: profile.version, ...(operation === 'status' ? { is_active: !profile.is_active } : {}),
            });
            toast.success('Profil printer diperbarui.'); setConfirmDelete(null); changed();
        } catch (error) { toast.error(error instanceof Error ? error.message : 'Profil belum dapat diperbarui.'); }
        finally { setMutating(false); }
    };
    const cancelJob = async (job: LabelJob) => {
        if (busy) return;
        setMutating(true);
        try { await hardwareRequest(`/platform/hardware/labels/${job.id}/outcome`, 'POST', { status: 'failed_before_send', error_code: 'cancelled' }); toast.success('Job dibatalkan sebelum pengiriman.'); changed(); }
        catch (error) { toast.error(error instanceof Error ? error.message : 'Job belum dapat dibatalkan.'); }
        finally { setMutating(false); }
    };
    return <AppLayout breadcrumbs={[{ title: 'Perangkat POS', href: '/platform/hardware' }]}><Head title="Perangkat POS" />
        <WorkspaceShortcutDropdown title="Perangkat POS" description="Printer label dan scanner" shortcuts={[
            ...tabs.map((item, index) => ({ label: item.name, description: `Buka ${item.name.toLowerCase()}`, keys: ['Alt', String(index + 1)] })),
            { label: 'Cari riwayat', description: 'Fokus pencarian job', keys: ['Alt', 'F'] },
        ]} />
        <div className="min-w-0 flex-1 space-y-6 p-4 lg:p-6">
            <header className="flex flex-wrap items-start justify-between gap-3"><div><h1 className="flex items-center gap-2 text-xl font-semibold"><Printer className="size-5 text-cyan-600 dark:text-cyan-300" />Perangkat POS</h1><p className="mt-2 text-sm text-muted-foreground">Label CT420D / Scanner HC-4208DB</p></div>
                <Badge variant="outline" className="text-amber-700 dark:text-amber-300">{printingEnabled ? 'Perangkat fisik belum diverifikasi' : 'Cetak perangkat nonaktif'}</Badge></header>
            <div className="grid min-w-0 gap-6 lg:grid-cols-[180px_minmax(0,1fr)]">
                <nav aria-label="Pengaturan perangkat" className="flex gap-1 overflow-x-auto border-b border-[var(--app-border)] pb-2 lg:flex-col lg:border-r lg:border-b-0 lg:pr-4">
                    {tabs.map((item) => <button key={item.id} onClick={() => setTab(item.id)} disabled={busy} aria-current={tab === item.id ? 'page' : undefined}
                        className={cn('flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors', tab === item.id ? 'bg-primary/10 font-medium text-primary' : 'hover:bg-muted')}><item.icon className={cn('size-4', item.tone)} />{item.name}</button>)}
                </nav>
                <div className="min-w-0 space-y-6">
                    {(tab === 'printers' || tab === 'labels') && <GatewayPanel connection={connection} hosts={allowedHosts} disabled={busy || (!manage && !print)} />}
                    {tab === 'printers' && <section className="space-y-4"><div className="flex flex-wrap items-center justify-between gap-3"><h2 className="text-base font-semibold">Profil printer</h2>{manage && <Button disabled={busy} onClick={() => setEditor({ profile: null })}><Plus />Tambah profil</Button>}</div>
                        {!profiles.length && <p className="border-y border-[var(--app-border)] py-12 text-center text-sm text-muted-foreground">Belum ada profil printer tersimpan.</p>}
                        <div className="grid gap-4 xl:grid-cols-2">{profiles.map((profile) => <article className="min-w-0 space-y-3 rounded-md border border-[var(--app-border)] p-4" key={profile.id}>
                            <div className="flex items-center justify-between gap-2"><h3 className="min-w-0 break-words text-sm font-semibold">{profile.name}</h3><Badge variant="outline" className={profile.deleted_at || !profile.is_active ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300'}>{profile.deleted_at ? 'Arsip' : profile.is_active ? 'Aktif' : 'Nonaktif'}</Badge></div>
                            <p className="break-all text-sm text-muted-foreground">{profile.printer_name}</p><p className="text-xs text-muted-foreground">{profile.gateway_host} / {profile.width_mm} x {profile.height_mm} mm / {profile.dpi} DPI</p>
                            <div className="flex flex-wrap items-center gap-1">{manage && (profile.deleted_at ? <Button variant="ghost" size="icon" title="Pulihkan profil" aria-label="Pulihkan profil" disabled={busy} onClick={() => void profileCommand(profile, 'restore')}><ArchiveRestore className="text-emerald-600" /></Button> : <>
                                <Button variant="ghost" size="icon" title="Edit profil" aria-label="Edit profil" disabled={busy} onClick={() => setEditor({ profile })}><Pencil className="text-sky-600" /></Button>
                                <Button variant="ghost" size="icon" title={profile.is_active ? 'Nonaktifkan profil' : 'Aktifkan profil'} aria-label={profile.is_active ? 'Nonaktifkan profil' : 'Aktifkan profil'} disabled={busy} onClick={() => void profileCommand(profile, 'status')}><Power className="text-amber-600" /></Button>
                                <Button variant="ghost" size="icon" title="Arsipkan profil" aria-label="Arsipkan profil" disabled={busy} onClick={() => setConfirmDelete(profile)}><Trash2 className="text-rose-600" /></Button></>)}
                                {print && !profile.deleted_at && <Button variant="outline" size="sm" disabled={busy || !profile.is_active || !printingEnabled || !can('inventory.view')}
                                    onClick={() => void runCreated({ kind: 'test_label', printer_id: profile.id, copies: 1 })}><Printer className="text-violet-600" />Test label</Button>}
                            </div>
                        </article>)}</div>
                    </section>}
                    {tab === 'labels' && <LabelComposer profiles={profiles} profileId={profileId} setProfileId={setProfileId} selected={selected} onSelected={setSelected}
                        canPrint={print && can('inventory.view')} printingEnabled={printingEnabled} busy={busy} onPrint={send} />}
                    {tab === 'history' && <section className="space-y-4"><form className="flex flex-wrap items-end gap-3 border-y border-[var(--app-border)] bg-[var(--app-panel)] p-4"
                        onSubmit={(event) => { event.preventDefault(); router.get('/platform/hardware', { q: search, status: status === 'all' ? '' : status, per_page: history.meta.per_page, page: 1 }, { preserveState: true, preserveScroll: true }); }}>
                        <div className="min-w-40 flex-1"><Input id="hardware-history-search" aria-label="Cari nomor job" placeholder="Cari nomor job" value={search} onChange={(event) => setSearch(event.target.value)} maxLength={150} /></div>
                        <Select value={status} onValueChange={setStatus}><SelectTrigger aria-label="Status job" className="w-52"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Semua status</SelectItem>{Object.entries(jobStatusLabels).map(([value, title]) => <SelectItem value={value} key={value}>{title}</SelectItem>)}</SelectContent></Select><Button type="submit" disabled={busy}><Search />Cari</Button>
                    </form><PrintJobTable history={history} actorId={user?.id ?? 0} canPrint={print} canReprint={can('platform.hardware.reprint')} manage={manage} disabled={busy} printingEnabled={printingEnabled}
                        onSend={(job) => void printing.run(() => Promise.resolve(job))} onCancel={(job) => void cancelJob(job)}
                        onReprint={async (job, reason, key) => { await printing.run(() => hardwareRequest<{ job: LabelJob }>(`/platform/hardware/labels/${job.id}/reprint`, 'POST', { reason, idempotency_key: key }).then((value) => value.job)); }} /></section>}
                    {tab === 'scanner' && <ScannerTestPanel expected={selected?.barcode} allowLookup={can('inventory.view')} />}
                </div>
            </div>
        </div>
        {editor && <PrinterProfileForm profile={editor.profile} host={connection.host} printers={connection.printers} hosts={allowedHosts} onClose={() => setEditor(null)} onSaved={changed} />}
        <Dialog open={confirmDelete !== null} onOpenChange={(open) => { if (!open && !busy) setConfirmDelete(null); }}><DialogContent><DialogHeader><DialogTitle>Arsipkan profil printer?</DialogTitle></DialogHeader><p className="text-sm text-muted-foreground">{confirmDelete?.name}. Riwayat job tetap disimpan.</p><DialogFooter><Button variant="outline" onClick={() => setConfirmDelete(null)} disabled={busy}>Batal</Button><Button variant="destructive" disabled={busy} onClick={() => { if (confirmDelete) void profileCommand(confirmDelete, 'delete'); }}><Trash2 />Arsipkan</Button></DialogFooter></DialogContent></Dialog>
    </AppLayout>;
}
