export function previewBase(quantity: string, ratio: string): string | null {
    const pattern = /^\d{1,14}(?:\.\d{1,6})?$/;
    if (!pattern.test(quantity) || !pattern.test(ratio)) return null;
    const scale = 1000000n;
    const micros = (value: string) => {
        const [whole, fraction = ''] = value.split('.');
        return BigInt(whole) * scale + BigInt(fraction.padEnd(6, '0'));
    };
    const q = micros(quantity),
        r = micros(ratio);
    if (q <= 0n || r < scale || (q * r) % scale !== 0n) return null;
    const base = (q * r) / scale;
    if (base > 99999999999999999999n) return null;
    return `${base / scale}.${(base % scale).toString().padStart(6, '0')}`;
}
