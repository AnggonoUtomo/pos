import { useEffect, useRef, useState } from 'react';
import type { PaymentDetail, PaymentRow } from '../types';

export function usePaymentDetail() {
    const controller = useRef<AbortController | null>(null);
    const [selected, setSelected] = useState<PaymentRow | null>(null);
    const [detail, setDetail] = useState<PaymentDetail | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    useEffect(() => () => controller.current?.abort(), []);
    const load = async (row: PaymentRow) => {
        controller.current?.abort();
        const request = new AbortController();
        controller.current = request;
        setSelected(row);
        setDetail(null);
        setLoading(true);
        setError('');
        try {
            const url =
                row.source_type === 'sale_return'
                    ? route('finance.payments.history.refund', { id: row.id })
                    : route('finance.payments.history.show', { direction: row.direction, id: row.id });
            const response = await fetch(url, {
                signal: request.signal,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Detail pembayaran belum dapat dimuat.');
            const data: PaymentDetail = await response.json();
            if (
                data.id !== row.id ||
                data.direction !== row.direction ||
                data.source_type !== row.source_type ||
                !/^\d+(?:\.\d{1,6})?$/.test(data.total) ||
                !Array.isArray(data.payments) ||
                !data.payments.every(
                    (line) =>
                        /^\d+(?:\.\d{1,6})?$/.test(line.amount) && typeof line.method_name === 'string' && typeof line.account_name === 'string',
                )
            )
                throw new Error('Data detail pembayaran tidak valid.');
            if (!request.signal.aborted) setDetail(data);
        } catch (failure) {
            if (!request.signal.aborted) setError(failure instanceof Error ? failure.message : 'Detail belum tersedia.');
        } finally {
            if (!request.signal.aborted) setLoading(false);
        }
    };
    const close = () => {
        controller.current?.abort();
        setSelected(null);
        setDetail(null);
        setLoading(false);
        setError('');
    };
    return {
        selected,
        detail,
        loading,
        error,
        load,
        close,
        retry: () => {
            if (selected) void load(selected);
        },
    };
}
