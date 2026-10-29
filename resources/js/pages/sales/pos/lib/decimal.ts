const formatter = new Intl.NumberFormat('id-ID');

export function localDecimal(canonical: string): string {
    const [whole, fraction = ''] = canonical.split('.');
    const tail = fraction.replace(/0+$/, '');
    return (canonical.startsWith('-') ? '-' : '') + formatter.format(BigInt(whole.replace('-', ''))) + (tail ? `,${tail}` : '');
}

export function money(canonical: string): string {
    return `Rp ${localDecimal(canonical)}`;
}

export function parseQuantity(local: string): string | null {
    if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,6})?$/.test(local.trim())) return null;
    return local.trim().replaceAll('.', '').replace(',', '.');
}

export function hasStock(stock: string): boolean {
    return /[1-9]/.test(stock);
}

export function parsePricingAmount(local: string): string | null {
    const parsed = parseQuantity(local);
    if (parsed === null || !/^\d{1,28}(?:\.\d{1,2})?$/.test(parsed)) return null;
    const [whole, fraction = ''] = parsed.split('.');
    return `${BigInt(whole)}.${fraction.padEnd(2, '0')}`;
}
