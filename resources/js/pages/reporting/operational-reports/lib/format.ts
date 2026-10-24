export function decimal(value: string): string {
    const [integer, fraction = ''] = value.split('.');
    const tail = fraction.replace(/0+$/, '');
    const whole = BigInt(integer);
    const sign = integer.startsWith('-') && whole === 0n && tail ? '-' : '';
    return sign + new Intl.NumberFormat('id-ID').format(whole) + (tail ? `,${tail}` : '');
}

export const money = (value: string) => `Rp ${decimal(value)}`;
