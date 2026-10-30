import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatDecimalInput } from '@/lib/decimal-input';
import { LoaderCircle, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { auditDate, type AuditDetail, type AuditRow, type AuditValue } from '../types';

function valueLabel(field: string, value: AuditValue | undefined): string {
    if (value === undefined) return 'Tidak dicatat';
    if (value === null) return 'Kosong';
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
    if (Array.isArray(value)) return value.join(', ') || 'Kosong';
    if (
        ['quantity', 'base_quantity', 'unit_cost', 'total_value', 'total', 'version', 'table_per_page', 'amount', 'conversion_ratio'].includes(field) &&
        /^-?\d+(?:\.\d+)?$/.test(value)
    ) {
        return (value.startsWith('-') ? '-' : '') + formatDecimalInput(value.replace(/^-/, ''));
    }
    if (field.endsWith('_at') && /^\d{4}-\d{2}-\d{2}T/.test(value)) return auditDate(value);
    return value || 'Kosong';
}

export function DetailDialog({ selected, onClose }: { selected: AuditRow | null; onClose: () => void }) {
    const [detail, setDetail] = useState<AuditDetail | null>(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [attempt, setAttempt] = useState(0);
    const id = selected?.id;
    useEffect(() => {
        if (!id) return;
        const controller = new AbortController();
        let active = true;
        const timer = setTimeout(() => controller.abort(), 15000);
        setDetail(null);
        setError('');
        setLoading(true);
        void (async () => {
            try {
                const response = await fetch(route('platform.audit-log.show', { id }), {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok)
                    throw new Error(response.status === 403 ? 'Akses detail aktivitas tidak diizinkan.' : 'Detail aktivitas belum dapat dimuat.');
                const result: AuditDetail = await response.json();
                if (result.id !== id || !result.field_labels || typeof result.field_labels !== 'object')
                    throw new Error('Data aktivitas tidak valid.');
                if (active) setDetail(result);
            } catch (failure) {
                if (active)
                    setError(
                        controller.signal.aborted
                            ? 'Permintaan terlalu lama. Coba muat ulang detail.'
                            : failure instanceof Error
                              ? failure.message
                              : 'Detail belum tersedia.',
                    );
            } finally {
                clearTimeout(timer);
                if (active) setLoading(false);
            }
        })();
        return () => {
            active = false;
            clearTimeout(timer);
            controller.abort();
        };
    }, [id, attempt]);
    const current = detail?.id === id ? detail : null;
    return (
        <Dialog
            open={Boolean(selected)}
            onOpenChange={(open) => {
                if (!open) onClose();
            }}
        >
            <DialogContent className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Detail aktivitas</DialogTitle>
                    <DialogDescription className="break-all">
                        #{selected?.id} - {selected?.reference ?? 'Tanpa objek'}
                    </DialogDescription>
                </DialogHeader>
                {loading && (
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat detail...
                    </p>
                )}
                {error && (
                    <div role="alert" className="flex items-center justify-between gap-3 text-sm">
                        <span className="text-destructive">{error}</span>
                        <Button
                            variant="outline"
                            size="icon"
                            title="Muat ulang detail"
                            aria-label="Muat ulang detail"
                            onClick={() => setAttempt((n) => n + 1)}
                        >
                            <RefreshCw className="text-sky-600 dark:text-sky-300" />
                        </Button>
                    </div>
                )}
                {current && (
                    <div className="min-w-0 space-y-4">
                        <dl className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 border-y border-[var(--app-border)] bg-[var(--app-panel-strong)] p-4 text-sm">
                            {(
                                [
                                    ['Waktu (WIB)', auditDate(current.occurred_at)],
                                    ['Pengguna', current.actor_name],
                                    ['Modul', current.module_name],
                                    ['Tindakan', current.event_name],
                                    ['Kode tindakan', current.event ?? '-'],
                                    ['ID objek', current.subject_id ?? '-'],
                                ] as const
                            ).map(([label, value]) => (
                                <div key={label} className="contents">
                                    <dt className="text-muted-foreground">{label}</dt>
                                    <dd className="break-all">{value}</dd>
                                </div>
                            ))}
                        </dl>
                        {Object.keys(current.field_labels).length === 0 ? (
                            <p className="text-muted-foreground py-6 text-center text-sm">Tidak ada rincian perubahan yang tersedia.</p>
                        ) : (
                            <div className="min-w-0 overflow-x-auto rounded-md border">
                                <table className="workspace-table w-full table-fixed text-sm" aria-label="Perubahan aktivitas">
                                    <thead className="text-left">
                                        <tr>
                                            {['Field', 'Sebelum', 'Sesudah'].map((label) => (
                                                <th key={label} scope="col" className="px-3 py-3 font-medium">
                                                    {label}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(current.field_labels).map(([field, label]) => (
                                            <tr key={field} className="align-top">
                                                <th scope="row" className="px-3 py-3 text-left font-medium break-words">
                                                    {label}
                                                </th>
                                                <td className="px-3 py-3 break-all">{valueLabel(field, current.before?.[field])}</td>
                                                <td className="px-3 py-3 break-all">{valueLabel(field, current.after?.[field])}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
