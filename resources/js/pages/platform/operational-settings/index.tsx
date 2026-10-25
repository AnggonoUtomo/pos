import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import type { ReceiptPaper } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Clock3, DatabaseBackup, LoaderCircle, Printer, RefreshCw, Save, SlidersHorizontal } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { ManualBackupDialog } from './components/manual-backup-dialog';
import { PrintPreview } from './components/print-preview';
import type { BackupStatus, Settings } from './types';

const utcDate = (value: string) => new Date(value.includes('T') ? value : `${value.replace(' ', 'T')}Z`);
const dateLabel = (value: string | null) => (value ? utcDate(value).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-');
const statuses = { running: 'Berjalan', succeeded: 'Berhasil', failed: 'Gagal' };

export default function OperationalSettings({ settings, backup }: { settings: Settings; backup: BackupStatus }) {
    const editable = usePermission().can('platform.operational-settings.manage');
    const form = useForm(settings);
    const manual = useForm({});
    const formRef = useRef<HTMLFormElement>(null);
    const [preview, setPreview] = useState(false);
    const [confirmBackup, setConfirmBackup] = useState(false);
    const { setData } = form;
    useEffect(() => setData(settings), [settings, setData]);
    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && editable) {
                event.preventDefault();
                formRef.current?.requestSubmit();
            }
            if (event.altKey && event.key.toLowerCase() === 'p') {
                event.preventDefault();
                setPreview(true);
            }
            if (event.altKey && event.key.toLowerCase() === 'b' && editable) {
                event.preventDefault();
                setConfirmBackup(true);
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [editable]);
    const seen = backup.scheduler_seen_at;
    const connected = seen !== null && Date.now() - utcDate(seen).getTime() < 5 * 60 * 1000;
    return (
        <AppLayout breadcrumbs={[{ title: 'Operasional', href: route('platform.operational-settings.index') }]}>
            <Head title="Pengaturan operasional" />
            <WorkspaceShortcutDropdown
                title="Pengaturan operasional"
                shortcuts={[
                    { label: 'Simpan', description: 'Simpan pengaturan', keys: ['Ctrl/Cmd', 'Enter'], disabled: !editable },
                    { label: 'Uji cetak', description: 'Buka contoh struk', keys: ['Alt', 'P'] },
                    { label: 'Backup sekarang', description: 'Konfirmasi backup manual', keys: ['Alt', 'B'], disabled: !editable },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-6 p-4 lg:p-6">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <SlidersHorizontal className="size-5 text-cyan-600 dark:text-cyan-300" />
                    Pengaturan operasional
                </h1>
                <div className="grid min-w-0 gap-8 xl:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
                    <form
                        ref={formRef}
                        className="min-w-0 space-y-6"
                        onSubmit={(event) => {
                            event.preventDefault();
                            if (!editable || form.processing) return;
                            form.put(route('platform.operational-settings.update'), {
                                preserveScroll: true,
                                onSuccess: () => toast.success('Pengaturan operasional tersimpan.'),
                                onError: () => toast.error('Pengaturan belum tersimpan. Periksa isian atau muat ulang.'),
                            });
                        }}
                    >
                        <fieldset disabled={!editable || form.processing} className="space-y-5 border-b pb-6">
                            <legend className="mb-4 flex items-center gap-2 text-base font-semibold">
                                <Printer className="size-4 text-violet-600 dark:text-violet-300" />
                                Cetak struk
                            </legend>
                            <div className="max-w-sm space-y-2">
                                <Label htmlFor="receipt-paper">Format kertas</Label>
                                <Select
                                    value={form.data.receipt_paper}
                                    disabled={!editable || form.processing}
                                    onValueChange={(value) => form.setData('receipt_paper', value as ReceiptPaper)}
                                >
                                    <SelectTrigger id="receipt-paper">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="a4">A4</SelectItem>
                                        <SelectItem value="58mm">58 mm</SelectItem>
                                        <SelectItem value="80mm">80 mm</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.receipt_paper} />
                            </div>
                            <div className="text-muted-foreground flex items-center gap-2 text-sm">
                                <Printer className="size-4" />
                                Dialog printer perangkat
                            </div>
                        </fieldset>
                        <Button type="button" variant="outline" onClick={() => setPreview(true)}>
                            <Printer className="text-violet-600 dark:text-violet-300" />
                            Uji cetak
                        </Button>
                        <fieldset disabled={!editable || form.processing} className="space-y-5 border-b pb-6">
                            <legend className="mb-4 flex items-center gap-2 text-base font-semibold">
                                <DatabaseBackup className="size-4 text-emerald-600 dark:text-emerald-300" />
                                Backup database
                            </legend>
                            <div className="flex items-center justify-between gap-4">
                                <Label htmlFor="backup-enabled">Backup otomatis harian</Label>
                                <Checkbox
                                    id="backup-enabled"
                                    checked={form.data.backup_enabled}
                                    onCheckedChange={(value) => form.setData('backup_enabled', value === true)}
                                    disabled={!editable || form.processing}
                                />
                            </div>
                            <InputError message={form.errors.backup_enabled} />
                            <div className="max-w-sm space-y-2">
                                <Label htmlFor="backup-time">Jam backup (WIB)</Label>
                                <Input
                                    id="backup-time"
                                    type="time"
                                    step={60}
                                    value={form.data.backup_time}
                                    onChange={(event) => form.setData('backup_time', event.target.value)}
                                    disabled={!editable || form.processing}
                                />
                                <InputError message={form.errors.backup_time} />
                            </div>
                            <Badge
                                variant="outline"
                                className={settings.backup_enabled ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted-foreground'}
                            >
                                {settings.backup_enabled ? `Jadwal tersimpan ${settings.backup_time} WIB` : 'Backup otomatis nonaktif'}
                            </Badge>
                        </fieldset>
                        <InputError message={form.errors.version ?? (form.errors as Record<string, string>).settings} />
                        {editable && (
                            <Button disabled={form.processing}>
                                {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan pengaturan
                            </Button>
                        )}
                    </form>
                    <aside className="min-w-0 space-y-5 border-t pt-6 xl:border-t-0 xl:border-l xl:pt-0 xl:pl-6" aria-label="Status backup">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="font-semibold">Status backup</h2>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                title="Muat ulang status"
                                aria-label="Muat ulang status"
                                onClick={() => router.reload({ only: ['backup'] })}
                            >
                                <RefreshCw className="text-sky-600 dark:text-sky-300" />
                            </Button>
                        </div>
                        <Badge
                            variant="outline"
                            className={connected ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'}
                        >
                            {connected ? 'Runner terhubung' : 'Runner belum terhubung'}
                        </Badge>
                        <div className="space-y-3 border-y py-4">
                            <h3 className="text-sm font-medium">Backup manual</h3>
                            <p className="text-muted-foreground text-xs">Folder server: backups</p>
                            {editable && (
                                <Button type="button" variant="outline" disabled={manual.processing} onClick={() => setConfirmBackup(true)}>
                                    <DatabaseBackup className="size-4 text-emerald-600 dark:text-emerald-300" />
                                    Backup sekarang
                                </Button>
                            )}
                            <InputError message={(manual.errors as Record<string, string>).backup} />
                            <p className="text-sm">
                                {backup.last_manual
                                    ? `${statuses[backup.last_manual.status]} - ${dateLabel(backup.last_manual.finished_at)}`
                                    : 'Belum ada backup manual'}
                            </p>
                            {backup.last_manual?.file && <p className="text-muted-foreground text-xs break-all">{backup.last_manual.file}</p>}
                        </div>
                        <dl className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-3 text-sm">
                            <dt className="text-muted-foreground flex items-start gap-1">
                                <Clock3 className="size-4 shrink-0 text-amber-600" />
                                Sinyal runner
                            </dt>
                            <dd className="break-words">{dateLabel(seen)}</dd>
                            <dt className="text-muted-foreground">Hasil terakhir</dt>
                            <dd>{backup.last_run ? statuses[backup.last_run.status] : 'Belum ada'}</dd>
                            <dt className="text-muted-foreground">Mulai (WIB)</dt>
                            <dd className="break-words">{dateLabel(backup.last_run?.started_at ?? null)}</dd>
                            <dt className="text-muted-foreground">Selesai (WIB)</dt>
                            <dd className="break-words">{dateLabel(backup.last_run?.finished_at ?? null)}</dd>
                            <dt className="text-muted-foreground">Percobaan</dt>
                            <dd>{backup.last_run?.attempts ?? 0} / 3</dd>
                            <dt className="text-muted-foreground">Salinan terpisah</dt>
                            <dd className="text-amber-700 dark:text-amber-300">Belum diverifikasi</dd>
                        </dl>
                        {backup.last_run?.status === 'failed' && (
                            <p role="alert" className="text-destructive text-sm">
                                Backup gagal. Periksa MySQL, tool backup dan kapasitas penyimpanan.
                            </p>
                        )}
                    </aside>
                </div>
            </section>
            <PrintPreview open={preview} onOpenChange={setPreview} paper={form.data.receipt_paper} />
            <ManualBackupDialog
                open={confirmBackup}
                processing={manual.processing}
                onOpenChange={setConfirmBackup}
                onConfirm={() => {
                    if (manual.processing) return;
                    manual.post(route('platform.operational-settings.backup'), {
                        preserveScroll: true,
                        onSuccess: () => {
                            setConfirmBackup(false);
                            toast.success('Backup manual berhasil.');
                        },
                        onError: () => {
                            setConfirmBackup(false);
                            toast.error('Backup manual gagal. Periksa status dan layanan backup.');
                        },
                    });
                }}
            />
        </AppLayout>
    );
}
