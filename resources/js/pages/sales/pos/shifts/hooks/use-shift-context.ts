import { useCallback, useEffect, useRef, useState } from 'react';
import { posRequest } from '../../lib/request';
import type { ShiftContext } from '../types';

export function useShiftContext(enabled: boolean) {
    const [data, setData] = useState<ShiftContext>({ active: null, drawers: [] });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);
    const request = useRef<AbortController | null>(null);
    const refresh = useCallback(async () => {
        if (!enabled) {
            setLoading(false);
            return;
        }
        request.current?.abort();
        const controller = new AbortController();
        request.current = controller;
        setLoading(true);
        try {
            const result = await posRequest<ShiftContext>(route('pos.shifts.current'), controller.signal);
            if (!controller.signal.aborted) {
                setData(result);
                setError('');
            }
        } catch (error) {
            if (!controller.signal.aborted) {
                setError(error instanceof Error ? error.message : 'Status shift belum dapat dimuat.');
                setData({ active: null, drawers: [] });
            }
        } finally {
            if (!controller.signal.aborted) setLoading(false);
        }
    }, [enabled]);
    useEffect(() => {
        void refresh();
        const focus = () => {
            void refresh();
        };
        window.addEventListener('focus', focus);
        return () => {
            window.removeEventListener('focus', focus);
            request.current?.abort();
        };
    }, [refresh]);
    return { ...data, error, loading, refresh };
}
