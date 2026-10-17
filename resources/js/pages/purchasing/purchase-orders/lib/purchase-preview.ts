const scale = 1000000n;
const maxQuantity = 99999999999999999999n;
const maxValue = 999999999999999999999999999999n;

function fixed(value: string): bigint | null {
    if (!/^\d{1,14}(?:\.\d{1,6})?$/.test(value)) return null;
    const [whole, fraction = ''] = value.split('.');
    const number = BigInt(whole) * scale + BigInt(fraction.padEnd(6, '0'));
    return number > 0n ? number : null;
}
function canonical(value: bigint): string {
    return `${value / scale}.${(value % scale).toString().padStart(6, '0')}`;
}
export function previewPurchaseLine(quantity: string, cost: string, ratio: string): { baseQuantity: string; totalValue: string } | null {
    const q = fixed(quantity);
    const c = fixed(cost);
    const r = fixed(ratio);
    if (q === null || c === null || r === null || r < scale) return null;
    const base = q * r;
    const total = q * c;
    if (base % scale !== 0n || total % scale !== 0n || base / scale > maxQuantity || total / scale > maxValue) return null;
    return { baseQuantity: canonical(base / scale), totalValue: canonical(total / scale) };
}
export function sumPurchaseValues(values: string[]): string | null {
    let total = 0n;
    for (const value of values) {
        if (!/^\d{1,24}\.\d{6}$/.test(value)) return null;
        total += BigInt(value.replace('.', ''));
    }
    return total <= maxValue ? canonical(total) : null;
}
