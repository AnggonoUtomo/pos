import assert from 'node:assert/strict';
import { registerHooks } from 'node:module';
registerHooks({ resolve(specifier, context, next) {
    try { return next(specifier, context); } catch (error) {
        if (error.code === 'ERR_MODULE_NOT_FOUND' && specifier.startsWith('.') && !/\.[a-z]+$/i.test(specifier)) return next(`${specifier}.ts`, context);
        throw error;
    }
} });
const { incrementScanQuantity } = await import('../../resources/js/lib/barcode-scan.ts');
const { barcodeDrawing, internalBarcode } = await import('../../resources/js/pages/inventory/items/lib/barcode.ts');
const { scanPurchase } = await import('../../resources/js/pages/purchasing/purchase-orders/lib/barcode.ts');
const { scanCart } = await import('../../resources/js/pages/sales/pos/lib/barcode.ts');

assert.equal(incrementScanQuantity('1,5'), '2,5');
assert.equal(incrementScanQuantity('9.007.199.254.740,123456'), '9.007.199.254.741,123456');
for (const value of ['1.5', '-1', '', '99999999999999']) assert.throws(() => incrementScanQuantity(value));
assert.match(internalBarcode(), /^\d{20}$/);
assert.notEqual(internalBarcode(), internalBarcode());
const drawing = barcodeDrawing('00123456789012');
assert.match(drawing.bars, /^110100/);
assert(drawing.modules > drawing.bars.length);
for (const value of ['', 'a\nb', 'kamera\u00e9']) assert.throws(() => barcodeDrawing(value));

const items = [{ id: 'camera', units: [{ unit_id: 'pcs', barcode: '00123' }, { unit_id: 'box', barcode: 'BOX-123' }] }];
const empty = [{ item_id: '', unit_id: '', quantity: '', unit_cost: '' }];
const first = scanPurchase(empty, items, '00123');
assert.deepEqual(first, [{ item_id: 'camera', unit_id: 'pcs', quantity: '1', unit_cost: '' }]);
const priced = [{ ...first[0], quantity: '1,5', unit_cost: '50.000' }];
assert.equal(scanPurchase(priced, items, '00123')[0].quantity, '2,5');
assert.equal(scanPurchase(priced, items, '00123')[0].unit_cost, '50.000');
assert.throws(() => scanPurchase(priced, items, 'BOX-123'));
assert.throws(() => scanPurchase(empty, items, '123'));

const item = { id: 'camera', stock: '8.000000', units: [{ id: 'pcs', ratio: '1.000000', price: '100.00' }, { id: 'box', ratio: '4.000000', price: '400.00' }] };
let lines = scanCart([], item, 'box');
lines = scanCart(lines, item, 'box');
assert.equal(lines[0].quantity, '2');
assert.throws(() => scanCart(lines, item, 'pcs'));
assert.equal(scanCart([], item, 'pcs')[0].unitId, 'pcs');
assert.throws(() => scanCart([], { ...item, units: [{ ...item.units[0], price: null }] }, 'pcs'));
assert.throws(() => scanCart([], { ...item, stock: '0' }, 'pcs'));
console.log('PASS: barcode encoding, leading zero, exact scan quantities, purchase unit/cost and aggregated POS stock.');
