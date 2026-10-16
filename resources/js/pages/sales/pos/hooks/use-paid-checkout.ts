import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { isPostedInvoice, readAttempt } from '../lib/checkout-storage';
import { PosRequestError, posRequest } from '../lib/request';
import type { CheckoutAttempt, PostedInvoice } from '../types';

export function usePaidCheckout(
    actorId: number | undefined,
    canPost: boolean,
    onRecovered: (attempt: CheckoutAttempt) => void,
    onPosted: () => void,
    onRejected: (message: string) => void,
) {
    const key = `pos.checkout.${actorId ?? 'guest'}`;
    const [ready, setReady] = useState(false);
    const [blocked, setBlocked] = useState(false);
    const [pending, setPending] = useState<CheckoutAttempt | null>(null);
    const pendingRef = useRef<CheckoutAttempt | null>(null);
    const flight = useRef(false);
    const postController = useRef<AbortController | null>(null);
    const readController = useRef<AbortController | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [lastId, setLastId] = useState<string | null>(null);
    const [receipt, setReceipt] = useState<PostedInvoice | null>(null);
    const [receiptOpen, setReceiptOpen] = useState(false);
    const [receiptBusy, setReceiptBusy] = useState(false);
    const [receiptError, setReceiptError] = useState('');

    useEffect(() => {
        try {
            const attempt = readAttempt(sessionStorage.getItem(key));
            pendingRef.current = attempt;
            setPending(attempt);
            setLastId(sessionStorage.getItem(`${key}.receipt`));
            if (attempt) onRecovered(attempt);
        } catch {
            setBlocked(true);
            setError('Pemulihan pembayaran tidak tersedia. Transaksi baru dikunci.');
        }
        setReady(true);
        return () => {
            postController.current?.abort();
            readController.current?.abort();
        };
    }, [key, onRecovered]);

    useEffect(() => {
        if (!pending) return;
        const warn = (event: BeforeUnloadEvent) => {
            event.preventDefault();
            event.returnValue = '';
        };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [pending]);

    const submit = useCallback(
        async (input: CheckoutAttempt) => {
            if (flight.current || !ready || !canPost || blocked) return;
            flight.current = true;
            readController.current?.abort();
            setReceiptBusy(false);
            const attempt = pendingRef.current ?? input;
            const controller = new AbortController();
            postController.current = controller;
            setError('');
            try {
                readAttempt(JSON.stringify(attempt));
                sessionStorage.setItem(key, JSON.stringify(attempt));
            } catch {
                flight.current = false;
                setError('Penyimpanan pemulihan pembayaran tidak tersedia. Pembayaran tidak dikirim.');
                toast.error('Pembayaran belum dikirim.');
                return;
            }
            pendingRef.current = attempt;
            setPending(attempt);
            setSubmitting(true);
            let timedOut = false;
            const timer = setTimeout(() => {
                timedOut = true;
                controller.abort();
            }, 30000);
            try {
                const result: unknown = await posRequest(route('pos.invoices.store'), controller.signal, attempt.payload);
                if (
                    !isPostedInvoice(result) ||
                    result.total !== attempt.total ||
                    result.warehouse_id !== attempt.payload.warehouse_id ||
                    result.customer_id !== attempt.payload.customer_id
                ) {
                    throw new Error('Respons pembayaran belum dapat dipastikan.');
                }
                if (controller.signal.aborted) throw new Error('Respons pembayaran belum terkonfirmasi.');
                try {
                    sessionStorage.setItem(`${key}.receipt`, result.id);
                    sessionStorage.removeItem(key);
                } catch {
                    // The server response is already authoritative; never turn this into a failed payment.
                    toast.warning('Transaksi tersimpan; penyimpanan bukti lokal belum diperbarui.');
                }
                pendingRef.current = null;
                setPending(null);
                setLastId(result.id);
                setReceipt(result);
                setReceiptError('');
                setReceiptOpen(true);
                onPosted();
                toast.success(`Penjualan ${result.number} berhasil diposting.`);
            } catch (failure) {
                if (controller.signal.aborted && !timedOut) return;
                if (failure instanceof PosRequestError && failure.status === 422 && !failure.fields.idempotency_key) {
                    try {
                        sessionStorage.removeItem(key);
                        pendingRef.current = null;
                        setPending(null);
                        setError(failure.message);
                        onRejected(failure.message);
                    } catch {
                        setError('Pemulihan pembayaran belum dapat diperbarui.');
                    }
                    toast.error(failure.message);
                } else {
                    const sessionError = failure instanceof PosRequestError && [401, 403, 419].includes(failure.status) ? `${failure.message} ` : '';
                    setError(`${sessionError}Status pembayaran belum dapat dipastikan. Ulangi pembayaran yang sama.`);
                    toast.error('Respons pembayaran belum terkonfirmasi.');
                }
            } finally {
                clearTimeout(timer);
                flight.current = false;
                if (!controller.signal.aborted || timedOut) setSubmitting(false);
            }
        },
        [key, ready, canPost, blocked, onPosted, onRejected],
    );

    const openReceipt = useCallback(async () => {
        if (!lastId || pendingRef.current || flight.current) return;
        readController.current?.abort();
        const controller = new AbortController();
        readController.current = controller;
        setReceipt(null);
        setReceiptOpen(true);
        setReceiptBusy(true);
        setReceiptError('');
        try {
            const result: unknown = await posRequest(route('pos.invoices.show', { id: lastId }), controller.signal);
            if (!isPostedInvoice(result)) throw new Error('Bukti transaksi belum dapat dimuat.');
            if (!controller.signal.aborted) setReceipt(result);
        } catch (failure) {
            if (!controller.signal.aborted) setReceiptError(failure instanceof Error ? failure.message : 'Bukti transaksi belum dapat dimuat.');
        } finally {
            if (!controller.signal.aborted) setReceiptBusy(false);
        }
    }, [lastId]);

    const clearError = useCallback(() => setError(''), []);
    const closeReceipt = useCallback((open: boolean) => {
        if (!open) {
            readController.current?.abort();
            setReceiptBusy(false);
        }
        setReceiptOpen(open);
    }, []);

    return {
        ready,
        blocked,
        pending,
        submitting,
        error,
        clearError,
        submit,
        lastId,
        receipt,
        receiptOpen,
        setReceiptOpen: closeReceipt,
        receiptBusy,
        receiptError,
        openReceipt,
    };
}
