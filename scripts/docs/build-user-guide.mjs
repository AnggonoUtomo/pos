import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const temporary = path.join(root, 'storage/framework/testing/user-guide');
const manifest = JSON.parse(await fs.readFile(path.join(temporary, 'manifest.json'), 'utf8'));
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.QA_PLAYWRIGHT_MODULE || 'playwright');
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];
try {
    for (const [source, expected] of Object.entries(manifest.sourceSha256)) {
        const actual = createHash('sha256').update(await fs.readFile(path.join(root, source))).digest('hex');
        assert.equal(actual, expected, `Naskah berubah setelah prepare: ${source}`);
    }
    for (const publication of manifest.publications) {
        const page = await browser.newPage({ viewport: { width: 658, height: 1000 } });
        await page.route('**/*', (route) => route.request().url().startsWith('file:') ? route.continue() : route.abort());
        await page.goto(pathToFileURL(publication.html).href);
        await page.emulateMedia({ media: 'print' });
        await page.evaluate(() => document.fonts.ready);
        const checks = await page.evaluate(() => ({
            paragraphs: document.querySelectorAll('main p').length,
            overflow: [...document.querySelectorAll('p,li,td,th,pre,h1,h2,h3')]
                .filter((element) => element.scrollWidth > element.clientWidth + 2)
                .map((element) => element.textContent.slice(0, 100)),
            brokenAnchors: [...document.querySelectorAll('a[href^="#"]')]
                .filter((element) => !document.getElementById(element.hash.slice(1))).map((element) => element.hash),
        }));
        assert(checks.paragraphs > 5, `Naskah kosong: ${publication.name}`);
        assert.deepEqual(checks.overflow, [], `Teks meluber: ${publication.name}`);
        assert.deepEqual(checks.brokenAnchors, [], `Daftar isi rusak: ${publication.name}`);
        const footer = `<div style="font-family:Arial;font-size:8px;color:#596966;width:100%;margin:0 18mm;display:flex;justify-content:space-between"><span>POS / Panduan pengguna / 27 Oktober 2026</span><span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>`;
        const pdf = await page.pdf({ path: publication.pdf, format: 'A4', printBackground: true,
            preferCSSPageSize: true, displayHeaderFooter: true, headerTemplate: '<span></span>', footerTemplate: footer,
            tagged: true, outline: true });
        assert.equal(pdf.subarray(0, 5).toString(), '%PDF-');
        assert(pdf.length > 15000, `PDF terlalu kecil: ${publication.name}`);
        results.push({ name: publication.name, bytes: pdf.length, sha256: createHash('sha256').update(pdf).digest('hex'), ...checks });
        await page.close();
        console.log(`PASS: ${publication.name} (${pdf.length} bytes), overflow 0, daftar isi valid`);
    }
    const report = { edition: manifest.edition, sourceRevision: manifest.sourceRevision, sourceSha256: manifest.sourceSha256, results };
    await fs.writeFile(path.join(root, 'output/pdf/user-guide/manifest.json'), JSON.stringify(report, null, 2) + '\n');
} finally {
    await browser.close();
}
