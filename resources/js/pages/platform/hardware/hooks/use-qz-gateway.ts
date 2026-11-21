import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { createQzGateway } from '../gateway/qz-gateway';
import type { GatewayPrinter } from '../types';

export function useQzGateway(allowedHosts: string[]) {
    const [printers, setPrinters] = useState<GatewayPrinter[]>([]);
    const [host, setHost] = useState(allowedHosts[0] ?? '');
    const [busy, setBusy] = useState(false);
    const [connected, setConnected] = useState(false);
    const [error, setError] = useState('');
    const mounted = useRef(true);
    const gateway = useRef(createQzGateway(allowedHosts, () => {
        if (mounted.current) { setConnected(false); setPrinters([]); }
    })).current;
    useEffect(() => {
        mounted.current = true;
        return () => { mounted.current = false; void gateway.disconnect().catch(() => {}); };
    }, [gateway]);

    const discover = async () => {
        if (busy) return;
        setBusy(true); setError(''); setPrinters([]);
        try {
            await gateway.connect(host);
            const found = await gateway.discover();
            if (mounted.current) { setPrinters(found); setConnected(true); toast.success(`${found.length} printer terdaftar ditemukan.`); }
        } catch {
            if (mounted.current) { setConnected(false); setError('Gateway belum tersedia atau izin ditolak. Periksa QZ Tray dan sertifikat pada PC tujuan.'); toast.error('Daftar printer belum dapat dibaca.'); }
        } finally { if (mounted.current) setBusy(false); }
    };
    const changeHost = (next: string) => {
        setHost(next); setConnected(false); setPrinters([]); setError('');
        void gateway.disconnect().catch(() => {});
    };
    return { gateway, printers, host, setHost: changeHost, busy, connected, error, discover };
}
