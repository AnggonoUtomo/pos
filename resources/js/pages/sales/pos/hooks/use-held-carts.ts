import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { checkoutKey } from '../lib/checkout-key';
import { PosRequestError, posRequest } from '../lib/request';
import type { HeldCartDraft, HeldCartList, HeldCartRef, HeldCartRow, RecalledCart } from '../types';

type Options = {
    enabled: boolean;
    hasLines: boolean;
    source: HeldCartRef | null;
    draft: () => HeldCartDraft;
    onHeld: () => void;
    onRecall: (cart: RecalledCart) => void;
    onCancelled: (id: string) => void;
};

export function useHeldCarts(options: Options) {
    const [mode, setMode] = useState<'hold' | 'list' | null>(null);
    const [label, setLabel] = useState('');
    const [records, setRecords] = useState<HeldCartList | null>(null);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);
    const [uncertain, setUncertain] = useState(false);
    const [confirmation, setConfirmation] = useState<{ kind: 'recall' | 'cancel'; row: HeldCartRow } | null>(null);
    const flight = useRef(false);
    const controller = useRef<AbortController | null>(null);
    const attempt = useRef<{ id: string; input: HeldCartDraft & { version: number; label: string } } | null>(null);
    useEffect(() => () => controller.current?.abort(), []);

    const run = async (task: (signal: AbortSignal) => Promise<void>, write = false) => {
        if (flight.current) return;
        flight.current = true;
        setBusy(true);
        setError('');
        const request = new AbortController();
        controller.current = request;
        const timer = setTimeout(() => request.abort(), 30000);
        try {
            await task(request.signal);
        } catch (failure) {
            const ambiguous = write && (!(failure instanceof PosRequestError) || failure.status >= 500);
            if (ambiguous) setUncertain(true);
            else {
                attempt.current = null;
                setUncertain(false);
            }
            const message = ambiguous
                ? 'Simpan belum terkonfirmasi. Ulangi simpan yang sama.'
                : failure instanceof Error
                  ? failure.message
                  : 'Keranjang belum dapat diproses.';
            setError(message);
            toast.error(message);
        } finally {
            clearTimeout(timer);
            flight.current = false;
            setBusy(false);
        }
    };
    const load = async (page = 1) => {
        await run(async (signal) => {
            setRecords(await posRequest<HeldCartList>(route('pos.held-carts.index', { page }), signal));
        });
    };
    const openHold = () => {
        if (!options.enabled || !options.hasLines || busy) return;
        setLabel(options.source?.label ?? '');
        attempt.current = null;
        setError('');
        setMode('hold');
    };
    const openList = () => {
        if (!options.enabled || busy) return;
        setRecords(null);
        setMode('list');
        void load();
    };
    const close = () => {
        if (busy || uncertain) return;
        setMode(null);
        setConfirmation(null);
    };
    const save = () => {
        if (!options.enabled || flight.current) return;
        void run(async (signal) => {
            if (!attempt.current)
                attempt.current = {
                    id: options.source?.id ?? checkoutKey(),
                    input: { ...options.draft(), version: options.source?.version ?? 0, label },
                };
            const result = await posRequest<HeldCartRef>(route('pos.held-carts.store', { id: attempt.current.id }), signal, attempt.current.input);
            if (!result || result.id.toLowerCase() !== attempt.current.id.toLowerCase() || !Number.isInteger(result.version))
                throw new Error('Respons simpan tidak valid.');
            attempt.current = null;
            setUncertain(false);
            setMode(null);
            options.onHeld();
            toast.success('Keranjang berhasil ditahan.');
        }, true);
    };
    const recall = (row: HeldCartRow) => {
        if (!options.enabled) return;
        void run(async (signal) => {
            const result = await posRequest<RecalledCart>(route('pos.held-carts.show', { id: row.id }), signal);
            options.onRecall(result);
            setConfirmation(null);
            setMode(null);
            toast[result.quote ? 'success' : 'warning'](
                result.quote ? 'Keranjang berhasil dipanggil.' : 'Keranjang dipanggil. Periksa bagian yang perlu diperbaiki.',
            );
        });
    };
    const requestRecall = (row: HeldCartRow) => {
        if (options.hasLines) setConfirmation({ kind: 'recall', row });
        else recall(row);
    };
    const confirm = () => {
        if (!confirmation || !options.enabled) return;
        if (confirmation.kind === 'recall') {
            recall(confirmation.row);
            return;
        }
        const row = confirmation.row;
        void run(async (signal) => {
            await posRequest(route('pos.held-carts.cancel', { id: row.id }), signal, { version: row.version });
            options.onCancelled(row.id);
            setConfirmation(null);
            setRecords(await posRequest<HeldCartList>(route('pos.held-carts.index'), signal));
            toast.success('Keranjang tertahan dibatalkan.');
        });
    };

    return {
        mode,
        label,
        setLabel,
        records,
        error,
        busy,
        uncertain,
        confirmation,
        setConfirmation,
        openHold,
        openList,
        close,
        save,
        load,
        requestRecall,
        requestCancel: (row: HeldCartRow) => setConfirmation({ kind: 'cancel', row }),
        confirm,
    };
}
