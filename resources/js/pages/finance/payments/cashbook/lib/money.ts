export function money(value: string): string {
    const negative = value.startsWith('-');
    const [whole, fraction = ''] = (negative ? value.slice(1) : value).split('.');
    const decimals = fraction.replace(/0+$/, '');
    return `Rp ${negative ? '-' : ''}${new Intl.NumberFormat('id-ID').format(BigInt(whole))}${decimals ? `,${decimals}` : ''}`;
}

export function parseMoney(value: string): string | null {
    const input = value.trim();
    if (!/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,6})?$/.test(input)) return null;
    return input.replaceAll('.', '').replace(',', '.');
}

export const kindLabel: Record<string, string> = {
    sale: 'Penjualan',
    purchase: 'Pembelian',
    sale_return: 'Refund pelanggan',
    purchase_return: 'Uang retur supplier',
    incoming: 'Pemasukan operasional',
    outgoing: 'Pengeluaran operasional',
    transfer: 'Transfer antar-akun',
    reversal: 'Pembalik mutasi',
};
export const dateLabel = (date: string) =>
    new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(date));
