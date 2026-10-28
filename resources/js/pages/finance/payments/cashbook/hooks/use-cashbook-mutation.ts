import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import type { Attempt } from '../types';

export function useCashbookMutation(userId: number | undefined, onSuccess: () => void) {
    const [pending, setPending] = useState<Attempt | null>(null);
    const [busy, setBusy] = useState(false);
    const [ready, setReady] = useState(false);
    const [uncertain, setUncertain] = useState(false);
    const [error, setError] = useState('');
    const [fields, setFields] = useState<Record<string, string>>({});
    const prefix = `finance.cashbook.pending.${userId}.`;
    const recover = useCallback(() => {
        try {
            const key = Object.keys(localStorage).find((key) => key.startsWith(prefix));
            const saved = key ? localStorage.getItem(key) : null;
            if (saved) {
                const attempt = JSON.parse(saved) as Attempt;
                if (
                    !/^[a-f0-9-]{36}$/i.test(attempt.requestId ?? '') ||
                    key !== prefix + attempt.requestId ||
                    !['opening', 'entry', 'reverse'].includes(attempt.action) ||
                    !attempt.data ||
                    typeof attempt.data !== 'object' ||
                    (attempt.action === 'reverse' && !/^[a-z0-9]{26}$/i.test(attempt.sourceId ?? ''))
                )
                    throw new Error();
                setPending(attempt);
                setUncertain(true);
                setError('Pencatatan sebelumnya belum terkonfirmasi.');
            } else {
                setPending(null);
                setUncertain(false);
            }
            setReady(true);
        } catch {
            setError('Penyimpanan pemulihan browser tidak tersedia atau tidak valid. Hubungi administrator.');
        }
    }, [prefix]);
    useEffect(() => {
        recover();
    }, [recover]);

    const submit = useCallback(
        async (attempt: Attempt) => {
            if (!ready || busy) return;
            setError('');
            setFields({});
            const key = prefix + attempt.requestId;
            try {
                localStorage.setItem(key, JSON.stringify(attempt));
            } catch {
                setError('Pencatatan belum dikirim karena penyimpanan pemulihan tidak tersedia.');
                return;
            }
            setPending(attempt);
            setBusy(true);
            try {
                const csrf = document.cookie
                    .split('; ')
                    .find((row) => row.startsWith('XSRF-TOKEN='))
                    ?.slice('XSRF-TOKEN='.length);
                const url =
                    attempt.action === 'opening'
                        ? route('finance.cashbook.open')
                        : attempt.action === 'reverse'
                          ? route('finance.cashbook.reverse', { id: attempt.sourceId })
                          : route('finance.cashbook.store');
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 30000);
                let response: Response;
                try {
                    response = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        signal: controller.signal,
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrf ? { 'X-XSRF-TOKEN': decodeURIComponent(csrf) } : {}),
                        },
                        body: JSON.stringify(attempt.data),
                    });
                } finally {
                    clearTimeout(timer);
                }
                const result = await response.json();
                if (!response.ok) {
                    if (response.status < 500 && !uncertain && response.status !== 409) {
                        localStorage.removeItem(key);
                        setPending(null);
                        setFields(
                            Object.fromEntries(
                                Object.entries(result.errors ?? {}).map(([field, values]) => [field, String((values as string[])[0])]),
                            ),
                        );
                        setError(result.message ?? 'Pencatatan ditolak.');
                        return;
                    }
                    throw new Error(result.message ?? 'Status pencatatan belum terkonfirmasi.');
                }
                if (typeof result.id !== 'string') throw new Error('Status pencatatan belum terkonfirmasi.');
                localStorage.removeItem(key);
                recover();
                toast.success('Pencatatan Buku Kas berhasil.');
                onSuccess();
            } catch (failure) {
                setUncertain(true);
                setError(
                    failure instanceof Error && failure.name !== 'AbortError'
                        ? failure.message
                        : 'Status belum terkonfirmasi. Ulangi pengiriman yang sama.',
                );
            } finally {
                setBusy(false);
            }
        },
        [ready, busy, prefix, uncertain, onSuccess, recover],
    );
    return {
        pending,
        busy,
        ready,
        uncertain,
        error,
        fields,
        submit,
        clearErrors: () => {
            setError('');
            setFields({});
        },
    };
}
