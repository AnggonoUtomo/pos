import { objectToFormData, type FormDataConvertible } from '@inertiajs/core';
import { useRef, useState } from 'react';
import { toast } from 'sonner';

export function useMasterCreate(onCreated?: (id: string) => void | Promise<void>) {
    const [processing, setProcessing] = useState(false);
    const [created, setCreated] = useState(false);
    const busy = useRef(false);
    const savedId = useRef<string | null>(null);
    const submit = async (
        url: string,
        data: Record<string, FormDataConvertible>,
        setErrors: (fields: Record<string, string>) => void,
    ): Promise<boolean> => {
        if (!onCreated || busy.current) return false;
        busy.current = true;
        setProcessing(true);
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 30000);
        try {
            if (!savedId.current) {
                const token = document.cookie
                    .split('; ')
                    .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
                    ?.slice(11);
                const multipart = Object.values(data).some((value) => value instanceof File);
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
                        ...(!multipart ? { 'Content-Type': 'application/json' } : {}),
                    },
                    body: multipart ? objectToFormData(data, undefined, null, 'indices') : JSON.stringify(data),
                });
                const body = await response.json().catch(() => null);
                if (!response.ok) {
                    if (body?.errors && typeof body.errors === 'object') {
                        setErrors(
                            Object.fromEntries(
                                Object.entries(body.errors as Record<string, string[]>).map(([key, values]) => [
                                    key,
                                    values[0] ?? 'Isian tidak valid.',
                                ]),
                            ),
                        );
                        toast.error('Master belum tersimpan. Periksa isian.');
                        return false;
                    }
                    throw new Error(
                        response.status === 403
                            ? 'Tidak memiliki izin menambah master ini.'
                            : 'Master belum dapat dipastikan tersimpan. Periksa daftar master sebelum mencoba lagi.',
                    );
                }
                if (typeof body?.id !== 'string' || !/^[0-9a-hjkmnp-tv-z]{26}$/i.test(body.id))
                    throw new Error('Hasil simpan belum dapat dipastikan. Periksa daftar master sebelum mencoba lagi.');
                savedId.current = body.id;
                setCreated(true);
            }
            // A failed option refresh retries the GET callback, never the committed create.
            await onCreated(savedId.current!);
            toast.success('Data master berhasil ditambahkan.');
            return true;
        } catch (failure) {
            const message = savedId.current
                ? 'Master sudah tersimpan, pilihan belum dimuat. Coba lagi atau periksa master.'
                : failure instanceof Error && failure.name !== 'AbortError'
                  ? failure.message
                  : 'Hasil simpan belum dapat dipastikan. Periksa daftar master sebelum mencoba lagi.';
            setErrors({ master: message });
            toast.error(message);
            return false;
        } finally {
            window.clearTimeout(timer);
            busy.current = false;
            setProcessing(false);
        }
    };
    return { processing, created, submit };
}
