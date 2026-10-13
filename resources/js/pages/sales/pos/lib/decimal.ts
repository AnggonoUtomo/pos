const formatter = new Intl.NumberFormat('id-ID');

export function localDecimal(canonical: string): string {
    const [whole, fraction = ''] = canonical.split('.');
    const tail = fraction.replace(/0+$/, '');
    return formatter.format(BigInt(whole)) + (tail ? `,${tail}` : '');
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
