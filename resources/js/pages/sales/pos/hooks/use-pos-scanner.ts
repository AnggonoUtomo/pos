import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { posRequest } from '../lib/request';
import type { PosItem } from '../types';

export function usePosScanner(warehouseId: string, customerId: string, enabled: boolean, onItem: (item: PosItem, unitId: string) => void) {
    const [busy, setBusy] = useState(false);
    const state = useRef({ queue: [] as string[], running: false, generation: 0, controller: null as AbortController | null });
    const receive = useRef(onItem);
    receive.current = onItem;
    useEffect(() => {
        const current = state.current;
        current.generation++;
        current.queue = [];
        current.running = false;
        current.controller?.abort();
        setBusy(false);
        return () => {
            current.generation++;
            current.queue = [];
            current.controller?.abort();
        };
    }, [warehouseId, customerId, enabled]);

    const enqueue = (code: string) => {
        const current = state.current;
        if (!enabled || !warehouseId || !customerId || !code.trim() || document.querySelector('[role="dialog"]')) return;
        if (current.queue.length >= 20) {
            toast.error('Antrean scan penuh. Tunggu hingga selesai.');
            return;
        }
        current.queue.push(code.trim());
        if (current.running) return;
        current.running = true;
        setBusy(true);
        const generation = current.generation;
        void (async () => {
            while (current.queue.length && current.generation === generation) {
                const value = current.queue.shift()!;
                const controller = new AbortController();
                current.controller = controller;
                const timer = setTimeout(() => controller.abort(), 15000);
                try {
                    const params = new URLSearchParams({ warehouse_id: warehouseId, customer_id: customerId, code: value });
                    const result = await posRequest<{ item: PosItem; unit_id: string }>(route('pos.scan') + '?' + params, controller.signal);
                    if (current.generation === generation) receive.current(result.item, result.unit_id);
                } catch (failure) {
                    if (current.generation === generation)
                        toast.error(
                            controller.signal.aborted
                                ? 'Scan habis waktu. Scan ulang kode yang belum masuk.'
                                : failure instanceof Error
                                  ? failure.message
                                  : 'Scan belum berhasil.',
                        );
                } finally {
                    clearTimeout(timer);
                }
            }
            if (current.generation === generation) {
                current.running = false;
                setBusy(false);
            }
        })();
    };
    return { busy, enqueue };
}
