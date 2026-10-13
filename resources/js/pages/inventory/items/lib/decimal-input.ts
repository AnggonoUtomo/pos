const integerFormatter = new Intl.NumberFormat('id-ID');

export function formatDecimalInput(value: string): string {
    const [whole, fraction = ''] = value.split('.');
    const digits = fraction.replace(/0+$/, '');
    return integerFormatter.format(BigInt(whole)) + (digits ? `,${digits}` : '');
}

export function parseDecimalInput(value: string, maximumFractionDigits = 6): string | null {
    const input = value.trim();
    if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(input) || (input.split(',')[1]?.length ?? 0) > maximumFractionDigits) return null;
    return input.replaceAll('.', '').replace(',', '.');
}
