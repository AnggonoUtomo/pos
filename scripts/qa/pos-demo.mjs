import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/pages/sales/pos/mock-data.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext } });
const { catalogItems, customerLevels, warehouses } = await import(`data:text/javascript;base64,${Buffer.from(compiled.outputText).toString('base64')}`);
const expected = {
    'DEMO-CCTV-IP-2MP': ['Kamera IP 2MP', 'UNIT', { UNIT: 1, BOX: 4 }],
    'DEMO-CCTV-NVR-8CH': ['NVR 8 Kanal', 'UNIT', { UNIT: 1, BOX: 2 }],
    'DEMO-CCTV-UTP-CAT6': ['Kabel UTP Cat6', 'M', { M: 1, ROLL: 305 }],
    'DEMO-ELEC-NYM-3X15': ['Kabel NYM 3x1.5 mm2', 'M', { M: 1, ROLL: 100 }],
    'DEMO-ELEC-MCB-6A': ['MCB 1P 6A', 'UNIT', { UNIT: 1, BOX: 12 }],
    'DEMO-CCTV-RJ45': ['Konektor RJ45', 'PCS', { PCS: 1, PACK: 100, BOX: 1000 }],
    'DEMO-CCTV-ADAPTER-12V': ['Adaptor CCTV 12V', 'UNIT', { UNIT: 1, BOX: 10 }],
    'DEMO-CCTV-BALUN': ['Balun Video', 'PCS', { PCS: 1, PAIR: 2 }],
    'DEMO-CCTV-KIT': ['Paket CCTV', 'SET', { SET: 1 }],
};
assert.deepEqual(catalogItems.map((item) => item.sku).sort(), Object.keys(expected).sort(), 'Katalog POS harus mengikuti demo CCTV/kelistrikan.');
assert.equal(new Set(catalogItems.map((item) => item.id)).size, 9);
for (const item of catalogItems) {
    const [name, base, units] = expected[item.sku];
    assert.equal(item.name, name);
    assert.equal(item.baseUnit.toUpperCase(), base);
    assert.deepEqual(Object.fromEntries(item.units.map((unit) => [unit.code.toUpperCase(), unit.ratio])), units);
    assert.equal(item.units[0].ratio, 1);
    assert(item.stock > 0 && Number.isFinite(item.stock));
    assert(item.units.every((unit) => unit.price > 0 && Number.isFinite(unit.price)));
}
assert.deepEqual(customerLevels.map((level) => level.name), ['Eceran', 'Grosir', 'Installer']);
assert.deepEqual(warehouses.map((warehouse) => warehouse.name), ['Gudang Utama', 'Gudang Tambahan']);
console.log('PASS: 9 produk, 18 konversi, level dan gudang mock sesuai domain CCTV/kelistrikan; harga/stok fiktif.');
