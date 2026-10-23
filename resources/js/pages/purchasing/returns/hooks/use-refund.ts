import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import type { RefundInput, RefundQuote, RefundSettlement, ReturnRow } from '../types';

export function useRefund(record: ReturnRow, onSettled: () => void) {
    const [quote, setQuote] = useState<RefundQuote | null>(null);
    const [settlement, setSettlement] = useState(record.settlement);
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [uncertain, setUncertain] = useState(false);
    const [error, setError] = useState('');
    const request = useRef<AbortController | null>(null);
    const pending = useRef<RefundInput | null>(null);
    const busy = useRef(false);
    const mounted = useRef(true);
    const reload = useCallback(async () => {
        if (busy.current || pending.current || record.settlement) return;
        request.current?.abort();
        const controller = new AbortController();
        request.current = controller;
        setQuote(null);
        setLoading(true);
        setError('');
        try {
            const response = await fetch(route('purchasing.returns.refund-quote', { id: record.id }), {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            });
            const body = await response.json().catch(() => null);
            if (!response.ok) throw new Error(message(response.status, body));
            if (
                !body ||
                body.id !== record.id ||
                body.version !== record.version ||
                !/^\d{1,24}\.\d{6}$/.test(body.total) ||
                !/^[a-f0-9]{64}$/.test(body.quote_fingerprint) ||
                !Array.isArray(body.methods) ||
                !Array.isArray(body.lines)
            ) {
                throw new Error('Quote refund tidak valid. Muat ulang daftar.');
            }
            if (!controller.signal.aborted) setQuote(body);
        } catch (failure) {
            if (!controller.signal.aborted) setError(failure instanceof Error ? failure.message : 'Quote belum dapat dimuat.');
        } finally {
            if (!controller.signal.aborted) setLoading(false);
        }
    }, [record.id, record.version, record.settlement]);
    useEffect(() => {
        mounted.current = true;
        if (!record.settlement) void reload();
        return () => {
            mounted.current = false;
            request.current?.abort();
        };
    }, [reload, record.settlement]);

    const submit = async (input?: RefundInput) => {
        if (busy.current || settlement) return;
        const payload = pending.current ?? input;
        if (!payload) return;
        pending.current = payload;
        busy.current = true;
        setSubmitting(true);
        setError('');
        const controller = new AbortController();
        request.current = controller;
        const timeout = window.setTimeout(() => controller.abort(), 30000);
        try {
            const token = document.cookie
                .split('; ')
                .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
                ?.slice(11);
            const response = await fetch(route('purchasing.returns.refund', { id: record.id }), {
                method: 'POST',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
                },
                body: JSON.stringify(payload),
            });
            const body = await response.json().catch(() => null);
            if (!response.ok) {
                // Only a definitive rejection permits a new quote/body. A 5xx may follow a committed transaction.
                if ([401, 403, 404, 419, 422].includes(response.status)) {
                    pending.current = null;
                    if (mounted.current) {
                        setUncertain(false);
                        setQuote(null);
                        setError(message(response.status, body));
                    }
                    toast.error(message(response.status, body));
                    return;
                }
                throw new Error('Respons penyelesaian belum dapat dipastikan.');
            }
            const result: RefundSettlement | undefined = body?.settlement;
            if (
                body?.id !== record.id ||
                body?.version !== payload.version ||
                !result?.id ||
                !['received', 'no_payment'].includes(result.outcome) ||
                !/^\d{1,24}\.\d{6}$/.test(result.total) ||
                !Array.isArray(result.payments) ||
                !result.settled_at
            )
                throw new Error('Respons penyelesaian tidak lengkap.');
            pending.current = null;
            if (mounted.current) {
                setUncertain(false);
                setSettlement(result);
                toast.success(
                    result.outcome === 'no_payment' ? 'Retur selesai tanpa pengembalian uang.' : 'Penerimaan uang supplier berhasil dicatat.',
                );
                onSettled();
            }
        } catch {
            if (mounted.current) {
                setUncertain(true);
                setError('Hasil belum terkonfirmasi. Periksa ulang dengan permintaan yang sama.');
                toast.error('Hasil refund belum terkonfirmasi.');
            }
        } finally {
            window.clearTimeout(timeout);
            busy.current = false;
            if (mounted.current) setSubmitting(false);
        }
    };
    return { quote, settlement, loading, submitting, uncertain, error, reload, submit };
}

function message(status: number, body: { errors?: Record<string, string[]> } | null): string {
    return (
        Object.values(body?.errors ?? {})[0]?.[0] ??
        {
            401: 'Sesi berakhir. Masuk kembali.',
            403: 'Akses refund tidak diizinkan.',
            404: 'Retur tidak ditemukan.',
            419: 'Sesi berakhir. Muat ulang halaman.',
            422: 'Quote berubah atau isian belum valid. Muat ulang quote dan konfirmasi kembali.',
        }[status] ??
        'Quote belum dapat dimuat. Coba lagi.'
    );
}
