import assert from 'node:assert/strict';
import { decimal } from '../../resources/js/pages/inventory/stock/types.ts';

for (const [input, expected] of [
    [undefined, '-'], ['0.000000', '0'], ['-0.000000', '0'],
    ['-0.500000', '-0,5'], ['-0.000001', '-0,000001'],
    ['-2.500000', '-2,5'], ['-2500.000000', '-2.500'],
    ['0.500000', '0,5'], ['2.000000', '2'],
    ['99999999999999.999999', '99.999.999.999.999,999999'],
    ['-99999999999999.999999', '-99.999.999.999.999,999999'],
]) assert.equal(decimal(input), expected);
console.log('PASS: 11 kasus quantity Indonesia, tanda minus pecahan dan presisi besar tanpa float.');
