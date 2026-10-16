import { useCallback, useEffect, useRef, useState } from 'react';
import { isPostedInvoice } from '../../lib/checkout-storage';
import { posRequest } from '../../lib/request';
import type { PostedInvoice } from '../../types';

export function useInvoiceReceipt() {
    const controller = useRef<AbortController | null>(null);
    const [id, setId] = useState<string | null>(null);
    const [receipt, setReceipt] = useState<PostedInvoice | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    useEffect(() => () => controller.current?.abort(), []);

    const load = useCallback(async (selected: string) => {
        controller.current?.abort();
        const request = new AbortController();
        controller.current = request;
        setId(selected);
        setReceipt(null);
        setLoading(true);
        setError('');
        try {
            const result: unknown = await posRequest(route('pos.invoices.show', { id: selected }), request.signal);
            if (!isPostedInvoice(result) || result.id !== selected) throw new Error('Bukti transaksi belum dapat dimuat.');
            if (!request.signal.aborted) setReceipt(result);
        } catch (failure) {
            if (!request.signal.aborted) setError(failure instanceof Error ? failure.message : 'Bukti transaksi belum dapat dimuat.');
        } finally {
            if (!request.signal.aborted) setLoading(false);
        }
    }, []);

    const close = (open: boolean) => {
        if (!open) {
            controller.current?.abort();
            setId(null);
            setReceipt(null);
            setLoading(false);
            setError('');
        }
    };

    return {
        id,
        receipt,
        loading,
        error,
        load,
        close,
        reload: () => {
            if (id) void load(id);
        },
    };
}
