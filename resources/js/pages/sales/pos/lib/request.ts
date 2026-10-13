export class PosRequestError extends Error {
    constructor(
        message: string,
        public fields: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

export async function posRequest<T>(url: string, signal: AbortSignal, payload?: object): Promise<T> {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);
    const response = await fetch(url, {
        method: payload ? 'POST' : 'GET',
        credentials: 'same-origin',
        signal,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(payload ? { 'Content-Type': 'application/json', ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}) } : {}),
        },
        ...(payload ? { body: JSON.stringify(payload) } : {}),
    });
    const result = await response.json().catch(() => null);
    if (!response.ok) {
        const fields: Record<string, string[]> = result?.errors ?? {};
        const message =
            Object.values(fields)[0]?.[0] ??
            { 401: 'Sesi berakhir. Masuk kembali.', 403: 'Akses POS tidak diizinkan.', 419: 'Sesi berakhir. Muat ulang halaman.' }[response.status] ??
            'Data POS belum dapat dimuat. Coba lagi.';
        throw new PosRequestError(message, fields);
    }
    return result as T;
}
