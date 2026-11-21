import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import type { ScannedIdentity, ScanResult } from '../types';

function isIdentity(value: unknown): value is ScannedIdentity {
    if (typeof value !== 'object' || value === null) return false;
    return (
        ['item_id', 'sku', 'name', 'unit_id', 'unit_name'].every((key) => typeof Reflect.get(value, key) === 'string') &&
        (Reflect.get(value, 'barcode') === null || typeof Reflect.get(value, 'barcode') === 'string')
    );
}

export function useScannerDiagnostics() {
    const [results, setResults] = useState<ScanResult[]>([]);
    const [pending, setPending] = useState(0);
    const sequence = useRef(0);
    const state = useRef({ queue: [] as string[], running: false, generation: 0, controller: null as AbortController | null });

    useEffect(() => {
        const current = state.current;
        return () => {
            current.generation++;
            current.queue = [];
            current.controller?.abort();
            current.running = false;
        };
    }, []);

    const clear = useCallback(() => {
        const current = state.current;
        current.generation++;
        current.queue = [];
        current.running = false;
        current.controller?.abort();
        setPending(0);
        setResults([]);
    }, []);

    const enqueue = useCallback((code: string) => {
        const current = state.current;
        if (document.querySelector('[role="dialog"]')) return;
        const value = code.trim();
        if (!value) return;
        if (current.queue.length + Number(current.running) >= 20) {
            toast.error('Antrean uji penuh (20). Tunggu hasil sebelum scan berikutnya.');
            return;
        }
        current.queue.push(value);
        setPending(current.queue.length + Number(current.running));
        if (current.running) return;
        current.running = true;
        const generation = current.generation;

        void (async () => {
            while (current.queue.length && generation === current.generation) {
                const input = current.queue.shift()!;
                const controller = new AbortController();
                current.controller = controller;
                setPending(current.queue.length + 1);
                const timer = setTimeout(() => controller.abort(), 15000);
                let result: Omit<ScanResult, 'id'>;
                try {
                    const response = await fetch(route('platform.hardware.scanner.resolve') + '?' + new URLSearchParams({ code: input }), {
                        signal: controller.signal,
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const body = await response.json().catch(() => null);
                    if (!response.ok) {
                        const validation = response.status === 422 && typeof body?.errors?.code?.[0] === 'string' ? body.errors.code[0] : null;
                        throw new Error(
                            validation ??
                                {
                                    401: 'Sesi berakhir. Masuk kembali.',
                                    403: 'Akses uji scanner tidak diizinkan.',
                                    404: 'Uji scanner tidak aktif.',
                                    429: 'Terlalu banyak uji. Tunggu sebentar.',
                                }[response.status] ??
                                'Hasil uji belum dapat dimuat.',
                        );
                    }
                    if (!isIdentity(body)) throw new Error('Respons uji tidak valid. Tidak ada hasil yang diterapkan.');
                    result = { code: input, status: 'found', message: 'Satuan jual dikenali', identity: body };
                } catch (failure) {
                    result = {
                        code: input,
                        status: 'error',
                        message: controller.signal.aborted
                            ? 'Uji habis waktu. Tidak diulang otomatis; periksa sebelum scan lagi.'
                            : failure instanceof Error
                              ? failure.message
                              : 'Uji belum berhasil.',
                    };
                } finally {
                    clearTimeout(timer);
                }
                if (generation !== current.generation) break;
                const row = { ...result, id: ++sequence.current };
                setResults((previous) => [row, ...previous].slice(0, 20));
                if (row.status === 'error') toast.error(row.message);
                setPending(current.queue.length);
            }
            if (generation === current.generation) {
                current.running = false;
                current.controller = null;
                setPending(0);
            }
        })();
    }, []);

    return { results, pending, busy: pending > 0, enqueue, clear };
}
