import assert from 'node:assert/strict';
import { formatRatioInput, parseRatioInput } from '../../resources/js/pages/inventory/items/lib/ratio-format.ts';

for (const [canonical, local] of [
    ['1.000000', '1'], ['6.000000', '6'], ['2.000000', '2'],
    ['1.500000', '1,5'], ['1000.500000', '1.000,5'], ['24.000001', '24,000001'],
    ['99999999999999.999999', '99.999.999.999.999,999999'], ['0.000001', '0,000001'],
]) assert.equal(formatRatioInput(canonical), local);
for (const [local, canonical] of [
    ['1', '1'], ['6', '6'], ['1,5', '1.5'], ['1.000', '1000'],
    ['1.000,500000', '1000.500000'], ['24,000001', '24.000001'],
    ['99.999.999.999.999,999999', '99999999999999.999999'], [' 12,5 ', '12.5'],
]) assert.equal(parseRatioInput(local), canonical);
for (const invalid of ['', '1.5', '1.000000', '1,0000001', '1,', '1e6', 'NaN', '-1', '1,000.5', '1..000']) {
    assert.equal(parseRatioInput(invalid), null, invalid);
}
assert.equal(parseRatioInput(formatRatioInput('99999999999999.999999')), '99999999999999.999999');
console.log('PASS: format Indonesia, grouping, comma input, invalid/ambiguous rejection and precision exact.');
