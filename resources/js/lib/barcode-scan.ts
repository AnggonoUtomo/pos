import { formatDecimalInput, parseDecimalInput } from './decimal-input';

export function scanQuantity(value: string): bigint {
    const canonical = parseDecimalInput(value);
    if (canonical === null || !/^\d{1,14}(?:\.\d{1,6})?$/.test(canonical)) throw new Error('Periksa kuantitas sebelum scan berikutnya.');
    const [whole, fraction = ''] = canonical.split('.');
    return BigInt(whole) * 1000000n + BigInt(fraction.padEnd(6, '0'));
}

export function incrementScanQuantity(value: string): string {
    const next = scanQuantity(value) + 1000000n;
    if (next >= 10n ** 20n) throw new Error('Kuantitas melebihi batas.');
    return formatDecimalInput(`${next / 1000000n}.${String(next % 1000000n).padStart(6, '0')}`);
}
