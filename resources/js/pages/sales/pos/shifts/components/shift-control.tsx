import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Link } from '@inertiajs/react';
import { Clock3, LoaderCircle, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { checkoutKey } from '../../lib/checkout-key';
import { money, parseQuantity } from '../../lib/decimal';
import { PosRequestError, posRequest } from '../../lib/request';
import type { useShiftContext } from '../hooks/use-shift-context';
import type { Drawer, ShiftDetail } from '../types';

type Attempt = { id: string; operation: 'open' | 'close'; payload: { account_id: string } | { counted: string; reason: string } };

export function ShiftControl({
    state,
    actorId,
    disabled = false,
    canOpenCashbook = false,
}: {
    state: ReturnType<typeof useShiftContext>;
    actorId: number;
    disabled?: boolean;
    canOpenCashbook?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [account, setAccount] = useState('');
    const [drawer, setDrawer] = useState<Drawer | null>(null);
    const [detail, setDetail] = useState<ShiftDetail | null>(null);
    const [counted, setCounted] = useState('');
    const [reason, setReason] = useState('');
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);
    const [pending, setPending] = useState<Attempt | null>(null);
    const [storageError, setStorageError] = useState(false);
    const flight = useRef(false);
    const key = `pos.shift.${actorId}`;
    useEffect(() => {
        try {
            const raw = sessionStorage.getItem(key);
            if (raw) {
                const value = JSON.parse(raw) as Attempt;
                if (
                    !/^[0-7][0-9a-hjkmnp-tv-z]{25}$/i.test(value.id) ||
                    !['open', 'close'].includes(value.operation) ||
                    !value.payload ||
                    typeof value.payload !== 'object'
                )
                    throw new Error();
                setPending(value);
                setOpen(true);
            }
        } catch {
            setOpen(true);
            setStorageError(true);
            setError('Pemulihan shift tidak tersedia. Muat ulang atau hubungi admin.');
        }
    }, [key]);
    useEffect(() => {
        if (!open || pending || storageError) return;
        const controller = new AbortController();
        setDetail(null);
        setDrawer(null);
        const url = state.active ? route('pos.shifts.show', { id: state.active.id }) : account ? route('pos.shifts.drawer', { id: account }) : null;
        if (url) {
            setBusy(true);
            void posRequest<ShiftDetail & { drawer: Drawer }>(url, controller.signal)
                .then((data) => {
                    if (!controller.signal.aborted) {
                        if (state.active) setDetail(data);
                        else setDrawer(data.drawer);
                    }
                })
                .catch((err: unknown) => {
                    if (!controller.signal.aborted) setError(err instanceof Error ? err.message : 'Posisi laci tidak tersedia.');
                })
                .finally(() => {
                    if (!controller.signal.aborted) setBusy(false);
                });
        }
        return () => controller.abort();
    }, [open, account, state.active, pending, storageError]);

    const submit = async () => {
        if (flight.current || disabled || storageError) return;
        let attempt = pending;
        if (!attempt) {
            const parsed = parseQuantity(counted);
            if (state.active && parsed === null) {
                setError('Masukkan uang fisik dengan format Indonesia.');
                return;
            }
            attempt = state.active
                ? { id: state.active.id, operation: 'close', payload: { counted: parsed!, reason } }
                : { id: checkoutKey(), operation: 'open', payload: { account_id: account } };
        }
        try {
            sessionStorage.setItem(key, JSON.stringify(attempt));
        } catch {
            setStorageError(true);
            setError('Pemulihan tidak tersedia; perubahan belum dikirim.');
            return;
        }
        flight.current = true;
        setBusy(true);
        setPending(attempt);
        setError('');
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 20000);
        try {
            await posRequest(route(`pos.shifts.${attempt.operation}`, { id: attempt.id }), controller.signal, attempt.payload);
            sessionStorage.removeItem(key);
            setPending(null);
            setOpen(false);
            setCounted('');
            setReason('');
            toast.success(attempt.operation === 'open' ? 'Shift dibuka.' : 'Shift ditutup. Selisih, jika ada, menunggu review.');
            await state.refresh();
        } catch (err) {
            if (err instanceof PosRequestError && [403, 404, 409, 422].includes(err.status)) {
                sessionStorage.removeItem(key);
                setPending(null);
            }
            setError(err instanceof Error && err.name !== 'AbortError' ? err.message : 'Hasil belum diketahui. Coba lagi dengan data yang sama.');
        } finally {
            clearTimeout(timer);
            flight.current = false;
            setBusy(false);
        }
    };

    return (
        <>
            <Button
                variant="outline"
                size="sm"
                aria-label={state.active ? 'Shift aktif' : 'Buka shift'}
                title={state.active ? `Shift aktif: ${state.active.account_name}` : 'Buka shift'}
                disabled={disabled || state.loading}
                onClick={() => {
                    if (!pending) setError('');
                    setOpen(true);
                    void state.refresh();
                }}
            >
                <Clock3 className={state.active ? 'text-emerald-600 dark:text-emerald-300' : 'text-amber-600 dark:text-amber-300'} />
                <span className="hidden xl:inline">{state.active ? 'Shift aktif' : 'Buka shift'}</span>
            </Button>
            <Dialog
                open={open}
                onOpenChange={(value) => {
                    if (!busy) setOpen(value);
                }}
            >
                <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{pending ? 'Pulihkan perubahan shift' : state.active ? 'Tutup shift kasir' : 'Buka shift kasir'}</DialogTitle>
                        <DialogDescription>
                            {state.active ? state.active.account_name : 'Modal mengikuti saldo Buku Kas, tanpa membuat pemasukan baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    {(error || state.error) && (
                        <p role="alert" className="text-destructive text-sm">
                            {error || state.error}
                        </p>
                    )}
                    {pending ? (
                        <p className="text-sm">
                            Permintaan {pending.operation === 'open' ? 'buka' : 'tutup'} shift belum dikonfirmasi. Periksa kembali dengan data yang
                            sama.
                        </p>
                    ) : state.active ? (
                        <>
                            <dl className="grid grid-cols-2 gap-3 text-sm">
                                <dt>Modal awal</dt>
                                <dd className="text-right tabular-nums">{money(state.active.opening_balance)}</dd>
                                <dt>Penjualan tunai bersih</dt>
                                <dd className="text-right tabular-nums">{detail ? money(detail.summary.cash_sales) : '-'}</dd>
                                <dt>Penjualan non-tunai</dt>
                                <dd className="text-right tabular-nums">{detail ? money(detail.summary.noncash_sales) : '-'}</dd>
                                <dt>Saldo seharusnya saat ini</dt>
                                <dd className="text-right font-semibold tabular-nums">{detail?.position ? money(detail.position.balance) : '-'}</dd>
                            </dl>
                            <p className="text-muted-foreground text-xs">
                                Saldo mencakup seluruh mutasi laci di Buku Kas, bukan hanya penjualan shift. Saldo diperiksa lagi saat ditutup.
                            </p>
                            <div className="space-y-2">
                                <Label htmlFor="shift-counted">Uang fisik terhitung</Label>
                                <Input
                                    id="shift-counted"
                                    inputMode="decimal"
                                    value={counted}
                                    onChange={(e) => setCounted(e.target.value)}
                                    disabled={busy}
                                    placeholder="0"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="shift-reason">Alasan selisih</Label>
                                <Textarea
                                    id="shift-reason"
                                    maxLength={200}
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    disabled={busy}
                                />
                            </div>
                        </>
                    ) : (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="shift-account">Laci kas</Label>
                                <Select value={account} onValueChange={setAccount} disabled={busy}>
                                    <SelectTrigger id="shift-account" className="w-full">
                                        <SelectValue placeholder="Pilih akun kas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {state.drawers.map((row) => (
                                            <SelectItem key={row.id} value={row.id}>
                                                {row.code} · {row.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {!state.drawers.length && (
                                <p className="text-sm">Belum ada akun kas dengan saldo awal Buku Kas. Minta admin menetapkannya dahulu.</p>
                            )}
                            {drawer?.balance && (
                                <p className="flex justify-between gap-3 text-sm">
                                    Modal dari Buku Kas <strong className="tabular-nums">{money(drawer.balance)}</strong>
                                </p>
                            )}
                            {drawer?.balance?.startsWith('-') && (
                                <p role="alert" className="text-amber-700 dark:text-amber-300">
                                    Saldo laci minus. Periksa Buku Kas.
                                </p>
                            )}
                            {canOpenCashbook && (
                                <Link className="text-primary text-sm underline" href="/finance/cashbook">
                                    Buku Kas
                                </Link>
                            )}
                        </>
                    )}
                    <DialogFooter className="flex-wrap gap-2">
                        <Button variant="outline" disabled={busy} asChild>
                            <Link href="/sales/cashier-shifts">Riwayat shift</Link>
                        </Button>
                        <Button variant="outline" disabled={busy || Boolean(pending)} onClick={() => setOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            disabled={disabled || busy || state.loading || storageError || (!pending && (state.active ? !detail?.position : !drawer))}
                            onClick={() => void submit()}
                        >
                            {busy ? <LoaderCircle className="animate-spin" /> : pending ? <RefreshCw /> : <Clock3 />}
                            {pending ? 'Periksa ulang' : state.active ? 'Tutup shift' : 'Mulai shift'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
