import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { createQzGateway } from '../gateway/qz-gateway';
import type { GatewayPrinter } from '../types';

export function useQzGateway(allowedHosts: string[], autoDetect = false) {
    const [printers, setPrinters] = useState<GatewayPrinter[]>([]);
    const [host, setHost] = useState(allowedHosts[0] ?? '');
    const [busy, setBusy] = useState(false);
    const [connected, setConnected] = useState(false);
    const [error, setError] = useState('');
    const mounted = useRef(true);
    const running = useRef(false);
    const started = useRef(false);
    const generation = useRef(0);
    const invalidate = useCallback(() => { generation.current++; }, []);
    const [gateway] = useState(() => createQzGateway(allowedHosts, () => {
        if (mounted.current) { setConnected(false); setPrinters([]); }
    }));
    useEffect(() => {
        mounted.current = true;
        return () => { mounted.current = false; invalidate(); void gateway.disconnect().catch(() => {}); };
    }, [gateway, invalidate]);

    const discover = useCallback(async (feedback = true) => {
        if (running.current || !host) return;
        running.current = true;
        const ticket = ++generation.current;
        setBusy(true); setError(''); setPrinters([]);
        try {
            await gateway.connect(host);
            if (!mounted.current || ticket !== generation.current) { await gateway.disconnect(); return; }
            const found = await gateway.discover();
            if (mounted.current && ticket === generation.current) {
                setPrinters(found); setConnected(true);
                if (feedback) toast.success(`${found.length} printer terdaftar ditemukan.`);
            }
        } catch {
            if (mounted.current && ticket === generation.current) {
                setConnected(false); setError('Gateway belum tersedia atau izin ditolak. Periksa QZ Tray dan sertifikat pada PC tujuan.');
                if (feedback) toast.error('Daftar printer belum dapat dibaca.');
            }
        } finally {
            running.current = false;
            if (mounted.current && ticket === generation.current) setBusy(false);
        }
    }, [gateway, host]);
    useEffect(() => {
        if (!autoDetect || started.current || !host) return;
        // Defer one tick so StrictMode cleanup can cancel the initial attempt.
        const timer = setTimeout(() => { started.current = true; void discover(false); }, 0);
        return () => clearTimeout(timer);
    }, [autoDetect, discover, host]);
    const changeHost = (next: string) => {
        if (running.current) return;
        generation.current++;
        setHost(next); setConnected(false); setPrinters([]); setError('');
        void gateway.disconnect().catch(() => {});
    };
    return { gateway, printers, host, setHost: changeHost, busy, connected, error, discover };
}
