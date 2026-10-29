export function chartRatios(values: string[], mode: 'max' | 'sum' = 'max'): number[] {
    const amounts = values.map((value) => {
        if (!/^\d{1,50}(?:\.\d{1,6})?$/.test(value)) throw new RangeError('Nominal chart harus decimal nonnegatif.');
        const [whole, fraction = ''] = value.split('.');
        return BigInt(whole) * 1_000_000n + BigInt(fraction.padEnd(6, '0'));
    });
    const denominator = amounts.reduce((total, value) => (mode === 'sum' ? total + value : value > total ? value : total), 0n);
    if (denominator === 0n) return values.map(() => 0);
    const hundredths = amounts.map((value) => (value * 10_000n) / denominator);
    // The final nonzero segment receives the visual rounding remainder, never a money adjustment.
    if (mode === 'sum') {
        const last = amounts.findLastIndex((value) => value > 0n);
        hundredths[last] += 10_000n - hundredths.reduce((sum, value) => sum + value, 0n);
    }
    return hundredths.map((value) => Number(value) / 100);
}
