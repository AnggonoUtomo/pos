import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { localDecimal, money, parseQuantity, hasStock } from '../../resources/js/pages/sales/pos/lib/decimal.ts';

assert.equal(localDecimal('1.000000'), '1');
assert.equal(localDecimal('1.125000'), '1,125');
assert.equal(money('999999999999999999.99'), 'Rp 999.999.999.999.999.999,99');
assert.equal(parseQuantity('1.000,125'), '1000.125');
for (const input of ['1.5', '1,0000001', '-1', '1e2', '', '1,']) assert.equal(parseQuantity(input), null);
assert.equal(hasStock('0.000000'), false);
assert.equal(hasStock('0.000001'), true);
const source = await readFile(new URL('../../resources/js/pages/sales/pos/index.tsx', import.meta.url), 'utf8');
assert(!source.includes('mock-data') && !source.includes('priceModifier') && !source.includes('submitPayment'));
console.log('PASS: exact Indonesian POS display/input; no mock catalog, price modifier or fake payment handler.');
