import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const source = readFileSync('resources/js/components/shared/charts/ratios.ts', 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const { chartRatios } = await import('data:text/javascript;base64,' + Buffer.from(compiled).toString('base64'));
assert.deepEqual(chartRatios(['0', '0.000000']), [0, 0]);
assert.deepEqual(chartRatios(['0', '5']), [0, 100]);
assert.deepEqual(chartRatios(['1', '3']), [33.33, 100]);
assert.deepEqual(chartRatios(['0.000001', '0.000002'], 'sum'), [33.33, 66.67]);
assert.deepEqual(chartRatios(['9007199254740993.123456', '9007199254740993.123456'], 'sum'), [50, 50]);
assert.deepEqual(chartRatios(['1', '1', '1'], 'sum'), [33.33, 33.33, 33.34]);
assert.deepEqual(chartRatios(['1', '0', '0'], 'sum'), [100, 0, 0]);
assert.deepEqual(chartRatios([]), []);
for (const bad of ['-1', 'NaN', '1e9', '1,5', '0.1234567']) assert.throws(() => chartRatios([bad]));
console.log('PASS: rasio exact, empty/zero, residual persentase, pecahan kecil dan angka besar.');
