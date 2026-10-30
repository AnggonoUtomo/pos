import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import path from 'node:path';
import { Worker } from 'node:worker_threads';

const require = createRequire(import.meta.url);

// Keep parser security checks in a bounded worker, without executing a shell.
const worker = new Worker(String.raw`
    const assert = require('node:assert/strict');
    const { parentPort } = require('node:worker_threads');
    const quote = require(${JSON.stringify(require.resolve('shell-quote'))});
    const { SourceMapConsumer } = require(${JSON.stringify(require.resolve('source-map-js'))});
    const { ExpandArguments } = require(${JSON.stringify(path.join(path.dirname(require.resolve('concurrently')), 'dist/src/command-parser/expand-arguments.js'))});
    const postcss = require(${JSON.stringify(require.resolve('postcss'))});
    const qs = require(${JSON.stringify(require.resolve('qs'))});
    for (const payload of [
        JSON.parse('{"a":[{"toString":"x"}]}'),
        qs.parse('a[0][toString]=x', { allowPrototypes: true }),
        qs.parse('a[][b]=c', { plainObjects: true }),
    ]) {
        for (const encodeValuesOnly of [false, true]) {
            assert.doesNotThrow(() => qs.stringify(payload, { arrayFormat: 'comma', encodeValuesOnly }));
        }
    }
    assert.equal(qs.stringify({ a: ['unit', 'dus'] }, { arrayFormat: 'comma' }), 'a=unit%2Cdus');
    const filters = { date_from: '2026-10-16', date_to: '2026-10-16', per_page: '10', search: 'kamera & kabel' };
    assert.deepEqual(qs.parse(qs.stringify(filters, { arrayFormat: 'brackets' })), filters);
    for (const separator of ['\n', '\r', '\u2028', '\u2029']) {
        assert.throws(() => quote.quote(['echo', 'ok', { comment: 'note' }, 'a' + separator + 'unexpected;#']), TypeError);
        assert.throws(() => quote.quote(quote.parse('echo http://example.test/#fragment').concat('a' + separator + 'unexpected;#')), TypeError);
    }
    assert.deepEqual(quote.parse(quote.quote(['echo', 'hello world', 'a; b'])), ['echo', 'hello world', 'a; b']);
    assert.equal(typeof quote.quote(['echo', 'ok', { comment: 'note' }, 'ordinary']), 'string');
    const args = ['hello world', 'a; b', '', 'a"b'];
    assert.deepEqual(quote.parse(new ExpandArguments(args).parse({ command: '{@}' }).command), args);
    assert.deepEqual(quote.parse(new ExpandArguments(args).parse({ command: '{*}' }).command), [args.join(' ')]);
    assert.deepEqual(quote.parse(new ExpandArguments(args).parse({ command: '{1}' }).command), [args[0]]);

    const basic = { version: 3, sources: ['input.js'], names: [], mappings: 'AAAA', sourcesContent: ['x'] };
    const indexed = (line, column = 0, map = basic) => ({ version: 3, sections: [{ offset: { line, column }, map }] });
    for (const invalid of [1000000000, -1, 0.5, '1', Infinity, NaN]) {
        assert.throws(() => new SourceMapConsumer(indexed(invalid)), /offset/i);
    }
    assert.throws(() => new SourceMapConsumer(indexed(0, -1)), /offset/i);
    assert.throws(() => new SourceMapConsumer(indexed(6000000, 0, indexed(6000000))), /offset/i);
    const consumer = new SourceMapConsumer(indexed(1));
    assert.deepEqual(consumer.originalPositionFor({ line: 2, column: 1 }), { source: 'input.js', line: 1, column: 0, name: null });
    assert.equal(consumer.sourceContentFor('input.js'), 'x');
    const cssMap = { ...basic, file: 'output.css', sources: ['input.css'], sourcesContent: ['a { color: red }'] };
    const inline = Buffer.from(JSON.stringify(cssMap)).toString('base64');
    for (const options of [
        { css: 'a { color: red }', map: { prev: cssMap, inline: false } },
        { css: 'a { color: red }\n/*# sourceMappingURL=data:application/json;base64,' + inline + ' */', map: { inline: false } },
    ]) {
        const result = postcss([]).process(options.css, { from: 'output.css', to: 'next.css', map: options.map }).sync();
        assert(result.css.includes('color: red'));
        assert(result.map.toJSON().sources.some((source) => source.endsWith('input.css')));
    }
    parentPort.postMessage('PASS: qs hostile comma arrays/normal query filters, shell-quote line terminators/parse-chain, source-map invalid/nested offsets, and normal controls');
`, { eval: true });

await new Promise((resolve, reject) => {
    let passed = false;
    const timer = setTimeout(() => {
        void worker.terminate();
        reject(new Error('Parser QA exceeded 10-second limit'));
    }, 10000);
    worker.once('message', (message) => { passed = true; console.log(message); });
    worker.once('error', (error) => { clearTimeout(timer); reject(error); });
    worker.once('exit', (code) => {
        clearTimeout(timer);
        try { assert.equal(code, 0); assert(passed); resolve(); } catch (error) { reject(error); }
    });
});
