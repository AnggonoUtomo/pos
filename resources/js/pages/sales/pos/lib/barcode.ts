import { incrementScanQuantity, scanQuantity } from '../../../../lib/barcode-scan';
import type { CartLine, PosItem } from '../types';

export function scanCart(lines: CartLine[], item: PosItem, unitId: string): CartLine[] {
    const unit = item.units.find((row) => row.id === unitId);
    if (!unit?.price || !/[1-9]/.test(unit.price)) throw new Error('Harga satuan belum tersedia.');
    const index = lines.findIndex((line) => line.item.id === item.id && line.unitId === unitId);
    if (index < 0 && lines.length >= 100) throw new Error('Maksimal 100 baris keranjang.');
    const next =
        index < 0
            ? [...lines, { item, unitId, quantity: '1' }]
            : lines.map((line, i) => (i === index ? { ...line, item, quantity: incrementScanQuantity(line.quantity) } : line));
    let needed = 0n;
    for (const line of next.filter((row) => row.item.id === item.id)) {
        const currentUnit = item.units.find((row) => row.id === line.unitId);
        if (!currentUnit) throw new Error('Satuan keranjang berubah. Periksa keranjang.');
        needed += scanQuantity(line.quantity) * scanQuantity(currentUnit.ratio.replace('.', ','));
    }
    const available = scanQuantity(item.stock.replace('.', ',')) * 1000000n;
    if (needed > available) throw new Error('Jumlah scan melebihi stok gudang.');
    return next;
}
