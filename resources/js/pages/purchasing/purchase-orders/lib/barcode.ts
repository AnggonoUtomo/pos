import { incrementScanQuantity } from '../../../../lib/barcode-scan';

type Line = { item_id: string; unit_id: string; quantity: string; unit_cost: string };
type Item = { id: string; units: { unit_id: string; barcode: string | null }[] };

export function scanPurchase(lines: Line[], items: Item[], code: string): Line[] {
    const needle = code.trim().toLowerCase();
    const matches = items.flatMap((item) =>
        item.units.filter((unit) => unit.barcode !== null && unit.barcode.toLowerCase() === needle).map((unit) => ({ item, unit })),
    );
    if (!needle || matches.length !== 1) throw new Error('Barcode tidak tersedia untuk pembelian.');
    const { item, unit } = matches[0];
    const index = lines.findIndex((line) => line.item_id === item.id);
    if (index >= 0) {
        if (lines[index].unit_id !== unit.unit_id)
            throw new Error('Barang sudah memakai satuan berbeda. Periksa satuan dan harga beli pada baris tersebut.');
        return lines.map((line, i) => (i === index ? { ...line, quantity: incrementScanQuantity(line.quantity) } : line));
    }
    const fresh = { item_id: item.id, unit_id: unit.unit_id, quantity: '1', unit_cost: '' };
    const empty = lines.findIndex((line) => Object.values(line).every((value) => value === ''));
    if (empty >= 0) return lines.map((line, i) => (i === empty ? fresh : line));
    if (lines.length >= 100) throw new Error('Maksimal 100 baris pembelian.');
    return [...lines, fresh];
}
