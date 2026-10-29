import assert from 'node:assert/strict';
import { Buffer } from 'node:buffer';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import ts from 'typescript';

const root = 'resources/js/pages';
const pages = [];
function scan(folder) {
    for (const entry of readdirSync(folder, { withFileTypes: true })) {
        const file = path.join(folder, entry.name);
        if (entry.isDirectory() && !['components', 'hooks', 'lib', 'auth'].includes(entry.name)) scan(file);
        if (entry.isFile() && entry.name.endsWith('.tsx')) pages.push(file);
    }
}
scan(root);
assert(pages.length > 15);
const entries = [];
for (const page of pages) {
    const file = page.replace(/\.tsx$/, '.guide.ts');
    assert(existsSync(file), `Panduan belum ada: ${file}`);
    const compiled = ts.transpileModule(readFileSync(file, 'utf8'), {
        compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 },
    }).outputText;
    const { default: guide } = await import('data:text/javascript;base64,' + Buffer.from(compiled).toString('base64'));
    assert.equal(typeof guide.title, 'string');
    assert(guide.title.length > 2);
    assert.equal(typeof guide.description, 'string');
    assert(guide.description.length > 15);
    assert(guide.sections.length >= 2);
    assert(guide.notes.length >= 1);
    for (const section of guide.sections) {
        assert.equal(typeof section.title, 'string');
        assert(section.steps.length >= 1);
        assert(section.steps.every((step) => typeof step === 'string' && step.length > 10));
    }
    assert(guide.notes.every((note) => typeof note === 'string' && note.length > 10));
    entries.push({
        page: path
            .relative(root, page)
            .replaceAll('\\', '/')
            .replace(/\.tsx$/, ''),
        title: guide.title,
    });
}
assert.equal(new Set(entries.map((entry) => entry.title)).size, entries.length, 'Judul panduan harus spesifik per halaman.');
const resolver = ts.transpileModule(readFileSync('resources/js/components/shared/page-guide/resolve.ts', 'utf8'), {
    compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 },
}).outputText;
const { guideKey, resolveGuide, fallbackGuide } = await import('data:text/javascript;base64,' + Buffer.from(resolver).toString('base64'));
assert.equal(guideKey('sales/pos/index'), '/resources/js/pages/sales/pos/index.guide.ts');
let loads = 0;
const example = { title: 'Panduan uji' };
const loaders = {
    [guideKey('dashboard')]: async () => {
        loads++;
        return example;
    },
};
assert.equal(loads, 0, 'Konten tidak dimuat sebelum diminta.');
assert.equal(await resolveGuide('dashboard', loaders), example);
assert.equal(loads, 1);
assert.equal(await resolveGuide('unknown/page', loaders), fallbackGuide);
assert.equal(loads, 1, 'Fallback tidak memuat konten halaman lain.');
await assert.rejects(
    resolveGuide('dashboard', {
        [guideKey('dashboard')]: async () => {
            throw new Error('offline');
        },
    }),
    /offline/,
);
console.log(JSON.stringify({ status: 'PASS', pages: entries.length, guides: entries }, null, 2));
