import assert from 'node:assert/strict';
import { paymentSummary, parsePaymentAmount } from '../../resources/js/pages/sales/pos/lib/payment.ts';
import { checkoutKey } from '../../resources/js/pages/sales/pos/lib/checkout-key.ts';
import { readAttempt, isPostedInvoice } from '../../resources/js/pages/sales/pos/lib/checkout-storage.ts';
import { parsePricingAmount } from '../../resources/js/pages/sales/pos/lib/decimal.ts';
import { decimal } from '../../resources/js/pages/reporting/operational-reports/lib/format.ts';

assert.equal(decimal('-0.000001'), '-0,000001');
assert.equal(decimal('9007199254740993.010000'), '9.007.199.254.740.993,01');

for (const [input, expected] of [['0', '0.00'], ['0,00', '0.00'], ['10', '10.00'], ['1.000,25', '1000.25'], ['1,234', null], ['-1', null], ['1.5', null]]) {
    assert.equal(parsePricingAmount(input), expected);
}

const methods = [{ id: 'cash', type: 'cash' }, { id: 'bank', type: 'transfer' }, { id: 'cash2', type: 'cash' }];
for (const [input, expected] of [['1', '1.00'], ['2,5', '2.50'], ['1.000,25', '1000.25'], ['0,01', '0.01'], ['0', null], ['-1', null], ['1.5', null], ['1,234', null], ['1e3', null], ['', null]]) {
    assert.equal(parsePaymentAmount(input), expected);
}
const rows = [{ methodId: 'bank', amount: '60.000' }, { methodId: 'cash', amount: '50.000' }];
const summary = paymentSummary('100000.00', rows, methods);
assert.equal(summary.valid, true);
assert.equal(summary.change, '10000.00');
assert.equal(summary.remaining, '0.00');
assert.equal(summary.tendered, '110000.00');
assert.equal(paymentSummary('100000.00', [{ methodId: 'bank', amount: '100.001' }], methods).change, '0.00');
assert.deepEqual(summary, paymentSummary('100000.00', [...rows].reverse(), methods));
for (const invalid of [[], [{ methodId: 'cash', amount: '1' }], [...rows, { methodId: 'cash2', amount: '1' }], [...rows, rows[0]], [{ methodId: 'bank', amount: '100.001' }], [{ methodId: 'bank', amount: '100.000' }, { methodId: 'cash', amount: '1' }], [{ methodId: 'unknown', amount: '100.000' }]]) {
    assert.equal(paymentSummary('100000.00', invalid, methods).valid, false);
}
assert.equal(paymentSummary('9007199254740993.17', [{ methodId: 'cash', amount: '9.007.199.254.740.994,23' }], methods).change, '1.06');
assert.equal(paymentSummary('9999999999999999999999999999.99', [{ methodId: 'cash', amount: '9.999.999.999.999.999.999.999.999.999,99' }], methods).valid, true);
const key = checkoutKey();
assert.match(key, /^[0-7][0-9A-HJKMNP-TV-Z]{25}$/);
assert.notEqual(key, checkoutKey());
assert.equal(checkoutKey(0, new Uint8Array(10)), '00000000000000000000000000');
assert.equal(checkoutKey(281474976710655, new Uint8Array(10).fill(255)), '7ZZZZZZZZZZZZZZZZZZZZZZZZZ');
const identifier = '01K7M000000000000000000000';
const item = { id: identifier, sku: 'CCTV', name: 'Kamera', base_unit_id: identifier, base_unit_name: 'Unit', stock: '8.000000', units: [{ id: identifier, code: 'PCS', name: 'Unit', ratio: '1.000000', price: '100000.00', barcode: null }] };
const attempt = { version: 1, payload: { idempotency_key: key, warehouse_id: identifier, customer_id: identifier, quote_fingerprint: 'a'.repeat(64), lines: [{ item_id: identifier, unit_id: identifier, quantity: '1.000000' }], payments: [{ method_id: identifier, amount: '100000.00' }] }, total: '100000.00', cart: [{ item, unitId: identifier, quantity: '1' }], methods: [{ id: identifier, code: 'CASH', name: 'Tunai', type: 'cash' }] };
assert.deepEqual(readAttempt(JSON.stringify(attempt)), attempt);
const priced = { ...attempt, payload: { ...attempt.payload, pricing: { discount_type: 'percent', discount_value: '10.00', tax_rate: '0.00' } } };
assert.deepEqual(readAttempt(JSON.stringify(priced)), priced);
assert.equal(readAttempt(null), null);
for (const bad of ['{', '{}', 'null', JSON.stringify({ ...attempt, cart: [] }), JSON.stringify({ ...attempt, payload: { ...attempt.payload, idempotency_key: 'bad' } })]) assert.throws(() => readAttempt(bad));
assert.equal(isPostedInvoice(null), false);
assert.equal(isPostedInvoice({ id: identifier }), false);
assert.equal(isPostedInvoice({ id: identifier, number: 'POS-QA', payments: {}, lines: [{}] }), false);
assert.throws(() => checkoutKey(-1));
assert.throws(() => checkoutKey(281474976710656));
assert.throws(() => checkoutKey(1, new Uint8Array(9)));
console.log('PASS: Indonesia/exact settlement and cryptographic ULID checkout keys.');
