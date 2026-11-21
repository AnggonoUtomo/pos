declare module 'qz-tray' {
    interface QzSdk {
        websocket: {
            isActive(): boolean;
            connect(options: { host: string; usingSecure: boolean; retries: number; keepAlive: number; port: { secure: number[]; insecure: number[] } }): Promise<void>;
            disconnect(): Promise<void>;
            setClosedCallbacks(callback: () => void): void;
        };
        security: { setCertificatePromise(handler: (resolve: (certificate: string | null) => void) => void): void };
        printers: { details(): Promise<unknown> };
        configs: { create(name: string, options: Record<string, unknown>): unknown };
        print(config: unknown, data: { type: 'pixel'; format: 'image'; flavor: 'base64'; data: string }[]): Promise<unknown>;
    }
    const qz: QzSdk;
    export default qz;
}
