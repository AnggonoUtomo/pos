import assert from 'node:assert/strict';
import { formatDecimalInput, parseDecimalInput } from '../../resources/js/lib/decimal-input.ts';
import { previewPurchaseLine, sumPurchaseValues } from '../../resources/js/pages/purchasing/purchase-orders/lib/purchase-preview.ts';

assert.equal(parseDecimalInput('120.000'), '120000');
assert.equal(formatDecimalInput('240000.000000'), '240.000');
assert.equal(formatDecimalInput('12.500000'), '12,5');
assert.deepEqual(previewPurchaseLine('2', '120000', '12'), { baseQuantity: '24.000000', totalValue: '240000.000000' });
assert.deepEqual(previewPurchaseLine('0.125', '900000', '100'), { baseQuantity: '12.500000', totalValue: '112500.000000' });
assert.equal(sumPurchaseValues(['240000.000000', '112500.000000']), '352500.000000');
for (const args of [['0', '1', '1'], ['1', '0', '1'], ['1e2', '1', '1'], ['1,5', '1', '1'],
    ['0.000001', '0.000001', '1'], ['99999999999999', '1', '2']]) assert.equal(previewPurchaseLine(...args), null);
assert.equal(sumPurchaseValues(['999999999999999999999999.999999', '1.000000']), null);
console.log('PASS: preview exact BigInt, Indonesia, positive values, rounding/range and total overflow.');
