import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import type { LabelGateway } from '../gateway/qz-gateway';
import { renderLabelPNG } from '../lib/label-renderer';
import type { LabelJob } from '../types';

type Claim = { job: LabelJob; token: string; attempt_id: string };

export async function hardwareRequest<T>(url: string, method = 'GET', data?: unknown): Promise<T> {
    const csrf = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length);
    const response = await fetch(url, { method, credentials: 'same-origin', signal: AbortSignal.timeout(15000), headers: {
        Accept: 'application/json', 'Content-Type': 'application/json', ...(csrf ? { 'X-XSRF-TOKEN': decodeURIComponent(csrf) } : {}),
    }, ...(data === undefined ? {} : { body: JSON.stringify(data) }) });
    const value = await response.json().catch(() => null);
    if (!response.ok || response.redirected || !value) {
        const first: unknown = value?.errors ? Object.values(value.errors).flat()[0] : value?.message;
        throw new Error(typeof first === 'string' ? first.slice(0, 500) : 'Permintaan belum berhasil. Muat ulang halaman dan periksa akses.');
    }
    return value as T;
}

export function useLabelPrint(gateway: LabelGateway, onChanged: () => void) {
    const [busy, setBusy] = useState(false);
    const [lastJob, setLastJob] = useState<LabelJob | null>(null);
    const running = useRef(false);
    const mounted = useRef(true);
    useEffect(() => { mounted.current = true; return () => { mounted.current = false; }; }, []);

    const run = async (create: () => Promise<LabelJob>) => {
        if (running.current) return;
        running.current = true; setBusy(true);
        let claim: Claim | null = null;
        let calledPrint = false;
        try {
            const job = await create();
            if (mounted.current) setLastJob(job);
            if (!mounted.current) return;
            if (job.status !== 'queued') { toast.info('Job ini sudah diproses. Periksa riwayat sebelum cetak ulang.'); return; }
            const image = renderLabelPNG(job.snapshot, job.printer);
            if (!gateway.isConnected(job.printer.gateway_host)) throw new Error('Hubungkan gateway profil tujuan terlebih dahulu.');
            const printers = await gateway.discover();
            if (!printers.some((printer) => printer.name === job.printer.printer_name)) throw new Error('Printer profil tidak ditemukan pada gateway ini.');
            if (!mounted.current) return;
            claim = await hardwareRequest<Claim>(`/platform/hardware/labels/${job.id}/dispatch`, 'POST', {});
            if (!mounted.current) throw new Error('Halaman telah ditutup sebelum pengiriman.');
            let timer: ReturnType<typeof setTimeout> | undefined;
            try {
                calledPrint = true;
                await Promise.race([gateway.print(job.printer, image.base64, job.copies, job.id),
                    new Promise<never>((_, reject) => { timer = setTimeout(() => reject(new Error('Konfirmasi gateway melewati batas waktu.')), 30000); })]);
            } finally { clearTimeout(timer); }
            const result = await hardwareRequest<{ job: LabelJob }>(`/platform/hardware/labels/${job.id}/outcome`, 'POST', {
                token: claim.token, attempt_id: claim.attempt_id, status: 'submitted', error_code: null,
            });
            if (mounted.current) setLastJob(result.job);
            toast.success('Label diterima gateway. Periksa hasil cetak fisik.');
            return result.job;
        } catch (error) {
            if (claim) {
                await hardwareRequest(`/platform/hardware/labels/${claim.job.id}/outcome`, 'POST', {
                    token: claim.token, attempt_id: claim.attempt_id, status: calledPrint ? 'uncertain' : 'failed_before_send',
                    error_code: calledPrint ? 'transport_error' : 'gateway_unavailable',
                }).catch(() => {});
            }
            toast.error(claim && calledPrint ? 'Hasil pengiriman belum pasti. Periksa printer; jangan kirim ulang otomatis.' : error instanceof Error ? error.message : 'Label belum dapat dikirim.');
        } finally {
            running.current = false;
            if (mounted.current) { setBusy(false); onChanged(); }
        }
    };
    return { busy, lastJob, run };
}
