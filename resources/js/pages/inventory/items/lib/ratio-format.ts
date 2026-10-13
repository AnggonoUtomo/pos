const integerFormatter = new Intl.NumberFormat('id-ID');

export function formatRatioInput(value: string): string {
    const [whole, fraction = ''] = value.split('.');
    const digits = fraction.replace(/0+$/, '');
    return integerFormatter.format(BigInt(whole)) + (digits ? `,${digits}` : '');
}

export function parseRatioInput(value: string): string | null {
    const input = value.trim();
    if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,6})?$/.test(input)) return null;
    return input.replaceAll('.', '').replace(',', '.');
}
