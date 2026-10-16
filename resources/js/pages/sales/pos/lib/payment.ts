const maxMinor = 10n ** 30n - 1n;

function decimal(minor: bigint): string {
    return `${minor / 100n}.${(minor % 100n).toString().padStart(2, '0')}`;
}

export function parsePaymentAmount(local: string): string | null {
    const text = local.trim();
    if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/.test(text)) return null;
    const [whole, fraction = ''] = text.replaceAll('.', '').split(',');
    if (whole.length > 28) return null;
    const minor = BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
    return minor > 0n && minor <= maxMinor ? decimal(minor) : null;
}

export function paymentSummary(
    total: string,
    rows: { methodId: string; amount: string }[],
    methods: { id: string; type: string }[],
): { valid: boolean; remaining: string; change: string; tendered: string; message: string } {
    const failed = (message: string) => ({ valid: false, remaining: total, change: '0.00', tendered: '0.00', message });
    if (!/^\d{1,28}\.\d{2}$/.test(total)) return failed('Total tagihan tidak valid.');
    const due = BigInt(total.replace('.', ''));
    if (due <= 0n || rows.length < 1 || rows.length > 10) return failed('Pilih 1 sampai 10 metode pembayaran.');
    let paid = 0n;
    let noncash = 0n;
    let cash = 0;
    const seen = new Set<string>();
    for (const row of rows) {
        const method = methods.find((method) => method.id === row.methodId);
        const amount = parsePaymentAmount(row.amount);
        if (!method || !['cash', 'debit', 'transfer'].includes(method.type)) return failed('Pilih metode pembayaran yang tersedia.');
        if (seen.has(row.methodId)) return failed('Metode pembayaran tidak boleh berulang.');
        if (!amount) return failed('Nominal harus positif dengan maksimal dua angka desimal.');
        seen.add(row.methodId);
        const minor = BigInt(amount.replace('.', ''));
        paid += minor;
        if (method.type === 'cash') cash++;
        else noncash += minor;
    }
    const result = {
        remaining: decimal(due > paid ? due - paid : 0n),
        change: decimal(cash === 1 && noncash < due && paid > due ? paid - due : 0n),
        tendered: decimal(paid),
    };
    let message = '';
    if (cash > 1) message = 'Maksimal satu metode tunai.';
    else if (paid > maxMinor) message = 'Total pembayaran melebihi batas.';
    else if (noncash > due) message = 'Pembayaran non-tunai melebihi tagihan.';
    else if (cash && noncash === due) message = 'Tunai tidak memiliki sisa tagihan.';
    else if (paid < due) message = 'Pembayaran belum lunas.';
    return { ...result, valid: !message, message };
}
