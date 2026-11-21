import type { GatewayPrinter, PrinterProfile } from '../types';

type Sdk = typeof import('qz-tray')['default'];

export function printConfiguration(profile: PrinterProfile, copies: number, jobId: string): Record<string, unknown> {
    if (!Number.isInteger(copies) || copies < 1 || copies > 100) throw new Error('Jumlah label tidak valid.');
    return { size: { width: profile.width_mm, height: profile.height_mm }, units: 'mm', margins: 0,
        density: profile.dpi / 25.4, orientation: profile.orientation, copies,
        scaleContent: true, interpolation: 'nearest-neighbor', colorType: 'grayscale', jobName: `POS Label ${jobId}` };
}

export function createQzGateway(allowedHosts: string[], onDisconnected?: () => void) {
    let sdk: Sdk | null = null;
    let connectedHost = '';
    const load = async () => {
        if (!sdk) {
            sdk = (await import('qz-tray')).default;
            // Unsigned mode keeps QZ's own permission prompts; no certificate bypass.
            sdk.security.setCertificatePromise((resolve) => resolve(null));
            sdk.websocket.setClosedCallbacks(() => { connectedHost = ''; onDisconnected?.(); });
        }
        return sdk;
    };
    return {
        async connect(host: string) {
            if (!allowedHosts.includes(host)) throw new Error('Host gateway tidak diizinkan.');
            const qz = await load();
            if (qz.websocket.isActive() && connectedHost !== host) await qz.websocket.disconnect();
            if (!qz.websocket.isActive()) {
                await qz.websocket.connect({ host, usingSecure: true, retries: 0, keepAlive: 0, port: { secure: [8181, 8282, 8383, 8484], insecure: [] } });
            }
            connectedHost = host;
        },
        isConnected(host?: string) { return sdk?.websocket.isActive() === true && (!host || host === connectedHost); },
        async discover(): Promise<GatewayPrinter[]> {
            if (!sdk?.websocket.isActive()) throw new Error('Hubungkan gateway terlebih dahulu.');
            const data = await sdk.printers.details();
            if (!Array.isArray(data) || data.length > 200) throw new Error('Daftar printer gateway tidak valid atau terlalu besar.');
            const result: GatewayPrinter[] = [];
            for (const row of data) {
                if (typeof row !== 'object' || row === null) throw new Error('Metadata printer tidak valid.');
                const name: unknown = Reflect.get(row, 'name');
                const driver: unknown = Reflect.get(row, 'driver');
                if (typeof name !== 'string' || name.length < 1 || name.length > 150 || [...name].some((char) => char.charCodeAt(0) < 32 || char.charCodeAt(0) === 127)) throw new Error('Nama printer gateway tidak valid.');
                result.push({ name, driver: typeof driver === 'string' ? driver.slice(0, 150) : '' });
            }
            return result;
        },
        async print(profile: PrinterProfile, base64: string, copies: number, jobId: string) {
            if (!sdk?.websocket.isActive() || connectedHost !== profile.gateway_host || !allowedHosts.includes(profile.gateway_host)) throw new Error('Gateway tujuan belum terhubung.');
            if (!/^[A-Za-z0-9+/]+=*$/.test(base64) || base64.length > 4_000_000) throw new Error('Gambar label tidak valid.');
            const config = sdk.configs.create(profile.printer_name, printConfiguration(profile, copies, jobId));
            await sdk.print(config, [{ type: 'pixel', format: 'image', flavor: 'base64', data: base64 }]);
        },
        async disconnect() {
            connectedHost = '';
            if (sdk?.websocket.isActive()) await sdk.websocket.disconnect();
        },
    };
}

export type LabelGateway = ReturnType<typeof createQzGateway>;
