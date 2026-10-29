import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { CheckCheck, ChevronLeft, ChevronRight, Eye, LoaderCircle, RefreshCw, ShoppingCart } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { money } from '../lib/decimal';
import { posRequest } from '../lib/request';
import { ShiftControl } from './components/shift-control';
import { useShiftContext } from './hooks/use-shift-context';
import type { CashierShift, ShiftDetail } from './types';

type Records = { data: CashierShift[]; meta: { current_page: number; last_page: number; total: number } };
const date = (value: string | null) =>
    value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : '-';
const status = (row: CashierShift) =>
    row.status === 'open'
        ? 'Aktif'
        : row.review_status === 'pending'
          ? 'Menunggu review'
          : row.review_status === 'reviewed'
            ? 'Sudah direview'
            : 'Selesai';

export default function CashierShifts() {
    const { user, can } = usePermission();
    const own = can('sales.pos.invoices.post');
    const shift = useShiftContext(own);
    const [page, setPage] = useState(1);
    const [version, setVersion] = useState(0);
    const [records, setRecords] = useState<Records | null>(null);
    const [detail, setDetail] = useState<ShiftDetail | null>(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [reviewing, setReviewing] = useState(false);
    const [note, setNote] = useState('');
    const flight = useRef(false);
    const refresh = useCallback(() => setVersion((value) => value + 1), []);
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError('');
        void posRequest<Records>(route('pos.shifts.data', { page }), controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) setRecords(data);
            })
            .catch((err: unknown) => {
                if (!controller.signal.aborted) {
                    setRecords(null);
                    setError(err instanceof Error ? err.message : 'Riwayat belum tersedia.');
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [page, version, shift.active?.id]);
    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if (event.altKey && event.key.toLowerCase() === 'r' && !document.querySelector('[role="dialog"]')) {
                event.preventDefault();
                refresh();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [refresh]);
    const show = async (id: string) => {
        if (flight.current) return;
        flight.current = true;
        setLoading(true);
        setError('');
        setNote('');
        try {
            setDetail(await posRequest<ShiftDetail>(route('pos.shifts.show', { id }), new AbortController().signal));
        } catch (err) {
            toast.error(err instanceof Error ? err.message : 'Detail belum tersedia.');
        } finally {
            flight.current = false;
            setLoading(false);
        }
    };
    const review = async () => {
        if (!detail || flight.current) return;
        flight.current = true;
        setReviewing(true);
        setError('');
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 20000);
        try {
            const result = await posRequest<{ shift: CashierShift }>(route('pos.shifts.review', { id: detail.shift.id }), controller.signal, {
                note,
            });
            setDetail({ ...detail, shift: result.shift });
            refresh();
            toast.success('Review selisih tersimpan. Buku Kas tidak diubah.');
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Review belum dikonfirmasi. Coba lagi dengan catatan yang sama.');
        } finally {
            clearTimeout(timer);
            flight.current = false;
            setReviewing(false);
        }
    };
    const row = detail?.shift;
    return (
        <AppLayout breadcrumbs={[{ title: 'Shift Kasir', href: '/sales/cashier-shifts' }]}>
            <Head title="Shift Kasir" />
            <WorkspaceShortcutDropdown
                title="Shift Kasir"
                description={can('sales.pos.shifts.view') ? 'Semua kasir' : 'Shift saya'}
                shortcuts={[
                    { label: 'Perbarui daftar', description: 'Muat ulang riwayat shift', keys: ['Alt', 'R'] },
                    { label: 'Tutup detail', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 space-y-4 p-4 lg:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Shift Kasir</h1>
                    <div className="flex flex-wrap gap-2">
                        {own && user && <ShiftControl state={shift} actorId={user.id} canOpenCashbook={can('finance.cashbook.view')} />}
                        <Button variant="outline" disabled={loading} onClick={refresh} title="Perbarui (Alt+R)">
                            <RefreshCw className="text-sky-600 dark:text-sky-300" />
                            Perbarui
                        </Button>
                        {can('sales.pos.invoices.create') && (
                            <Button variant="outline" asChild>
                                <Link href="/pos">
                                    <ShoppingCart className="text-emerald-600 dark:text-emerald-300" />
                                    POS
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
                {error && !detail && (
                    <p role="alert" className="text-destructive text-sm">
                        {error}
                    </p>
                )}
                {shift.error && (
                    <p role="alert" className="text-destructive text-sm">
                        {shift.error}
                    </p>
                )}
                <div className="overflow-x-auto rounded-md border bg-[var(--app-panel)]" aria-busy={loading}>
                    <table
                        className="workspace-table w-full min-w-[1000px] text-sm [&_td]:px-4 [&_td]:py-3 [&_th]:px-4 [&_th]:py-3"
                        aria-label="Riwayat shift kasir"
                    >
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                {['Dibuka (WIB)', 'Kasir', 'Laci', 'Modal', 'Uang fisik', 'Selisih', 'Status', ''].map((title, i) => (
                                    <th key={i}>{title}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {records?.data.map((item) => (
                                <tr key={item.id} className="border-t align-top">
                                    <td className="whitespace-nowrap">{date(item.opened_at)}</td>
                                    <td>{item.actor_name}</td>
                                    <td>{item.account_name}</td>
                                    <td className="whitespace-nowrap tabular-nums">{money(item.opening_balance)}</td>
                                    <td className="whitespace-nowrap tabular-nums">{item.counted_cash === null ? '-' : money(item.counted_cash)}</td>
                                    <td className="whitespace-nowrap tabular-nums">{item.difference === null ? '-' : money(item.difference)}</td>
                                    <td>
                                        <Badge
                                            variant="outline"
                                            className={
                                                item.status === 'open'
                                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                    : item.review_status === 'pending'
                                                      ? 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                                      : 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                            }
                                        >
                                            {status(item)}
                                        </Badge>
                                    </td>
                                    <td>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            disabled={loading}
                                            aria-label={`Detail shift ${item.actor_name}`}
                                            title="Detail shift"
                                            onClick={() => void show(item.id)}
                                        >
                                            <Eye className="text-sky-600 dark:text-sky-300" />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {!records?.data.length && (
                                <tr>
                                    <td colSpan={8} className="text-muted-foreground h-24 text-center">
                                        {loading ? 'Memuat...' : 'Belum ada shift.'}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="flex items-center justify-between gap-3 text-sm">
                    <span>
                        {records?.meta.total ?? 0} shift · Halaman {page} / {records?.meta.last_page ?? 1}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            size="icon"
                            variant="outline"
                            title="Sebelumnya"
                            aria-label="Halaman sebelumnya"
                            disabled={loading || page <= 1}
                            onClick={() => setPage(page - 1)}
                        >
                            <ChevronLeft />
                        </Button>
                        <Button
                            size="icon"
                            variant="outline"
                            title="Berikutnya"
                            aria-label="Halaman berikutnya"
                            disabled={loading || !records || page >= records.meta.last_page}
                            onClick={() => setPage(page + 1)}
                        >
                            <ChevronRight />
                        </Button>
                    </div>
                </div>
            </section>
            <Dialog
                open={Boolean(detail)}
                onOpenChange={(value) => {
                    if (!value && !reviewing) {
                        setDetail(null);
                        setError('');
                    }
                }}
            >
                <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Detail shift</DialogTitle>
                        <DialogDescription>
                            {row?.actor_name} · {row?.account_name}
                        </DialogDescription>
                    </DialogHeader>
                    {row && detail && (
                        <>
                            <p className="text-sm font-medium">{status(row)}</p>
                            <dl className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-3 text-sm">
                                <dt>Dibuka</dt>
                                <dd className="text-right">{date(row.opened_at)}</dd>
                                <dt>Ditutup</dt>
                                <dd className="text-right">{date(row.closed_at)}</dd>
                                <dt>Modal awal</dt>
                                <dd className="text-right break-words tabular-nums">{money(row.opening_balance)}</dd>
                                <dt>Jumlah penjualan</dt>
                                <dd className="text-right">{detail.summary.invoice_count}</dd>
                                <dt>Penjualan tunai bersih</dt>
                                <dd className="text-right break-words tabular-nums">{money(detail.summary.cash_sales)}</dd>
                                <dt>Penjualan non-tunai</dt>
                                <dd className="text-right break-words tabular-nums">{money(detail.summary.noncash_sales)}</dd>
                                {detail.summary.other_cash_net !== undefined && (
                                    <>
                                        <dt>Mutasi kas lainnya (neto)</dt>
                                        <dd className="text-right break-words tabular-nums">{money(detail.summary.other_cash_net)}</dd>
                                    </>
                                )}
                                <dt>{row.status === 'open' ? 'Saldo laci saat ini' : 'Saldo saat tutup'}</dt>
                                <dd className="text-right break-words tabular-nums">{money(row.expected_cash ?? detail.position?.balance ?? '0')}</dd>
                                <dt>Uang fisik</dt>
                                <dd className="text-right break-words tabular-nums">{row.counted_cash === null ? '-' : money(row.counted_cash)}</dd>
                                <dt>Selisih</dt>
                                <dd className="text-right font-semibold break-words tabular-nums">
                                    {row.difference === null ? '-' : money(row.difference)}
                                </dd>
                            </dl>
                            {row.close_reason && <p className="text-sm break-words">Alasan: {row.close_reason}</p>}
                            {row.review_note && (
                                <p className="text-sm break-words">
                                    Review ({date(row.reviewed_at)}): {row.review_note}
                                </p>
                            )}
                            {error && (
                                <p role="alert" className="text-destructive text-sm">
                                    {error}
                                </p>
                            )}
                            {row.review_status === 'pending' && can('sales.pos.shifts.review') && (
                                <div className="space-y-3 border-t pt-3">
                                    <Label htmlFor="shift-review">Catatan pemeriksaan</Label>
                                    <Textarea
                                        id="shift-review"
                                        maxLength={200}
                                        value={note}
                                        onChange={(e) => setNote(e.target.value)}
                                        disabled={reviewing}
                                    />
                                    <Button disabled={reviewing || !note.trim()} onClick={() => void review()}>
                                        {reviewing ? <LoaderCircle className="animate-spin" /> : <CheckCheck />}Sahkan review
                                    </Button>
                                </div>
                            )}
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
