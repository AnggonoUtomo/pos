import assert from 'node:assert/strict';
import { Buffer } from 'node:buffer';
import { readFileSync } from 'node:fs';
import ts from 'typescript';
const output = ts.transpileModule(readFileSync('resources/js/components/shared/table-pagination-model.ts', 'utf8'), {
    compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 },
}).outputText;
const { pageNumbers, tablePageSizes } = await import('data:text/javascript;base64,' + Buffer.from(output).toString('base64'));
assert.deepEqual(tablePageSizes, [10, 25, 50, 100]);
assert.deepEqual(pageNumbers(1, 1), [1]);
assert.deepEqual(pageNumbers(2, 4), [1, 2, 3, 4]);
for (const current of [1, 2, 5, 50, 99, 100, 1000]) {
    const pages = pageNumbers(current, 100);
    const numbers = pages.filter((value) => typeof value === 'number');
    assert.equal(numbers[0], 1);
    assert.equal(numbers.at(-1), 100);
    assert.equal(new Set(numbers).size, numbers.length);
    assert(numbers.includes(Math.min(current, 100)));
    assert(pages.length <= 9);
}
console.log('PASS pagination: sizes, first/middle/last, ellipsis and bounded count');
