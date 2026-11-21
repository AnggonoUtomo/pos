import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan instalasi Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const directory = path.join(root, 'storage/app/private', `qz-setup-${randomBytes(8).toString('hex')}`);
await mkdir(directory, { recursive: true });
const report = { nativeConnection: 'NOT_RUN', physical: 'PHYSICAL_PENDING', printCalled: false };
let browser;
try {
    browser = await chromium.launch({ channel: 'chrome', headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto('https://pos.test/login', { waitUntil: 'domcontentloaded' });
    assert.equal(new URL(page.url()).hostname, 'pos.test');
    await page.addScriptTag({ content: await readFile(path.join(root, 'node_modules/qz-tray/qz-tray.js'), 'utf8') });
    const result = await page.evaluate(async () => {
        const qz = window.qz;
        qz.security.setCertificatePromise((resolve) => resolve(null));
        try {
            return await Promise.race([
                (async () => {
                    await qz.websocket.connect({ host: 'localhost', usingSecure: true, retries: 0, keepAlive: 0,
                        port: { secure: [8181, 8282, 8383, 8484], insecure: [] } });
                    return { version: await qz.api.getVersion(), printers: await qz.printers.details() };
                })(),
                new Promise((_, reject) => setTimeout(() => reject(new Error('QZ_PERMISSION_TIMEOUT')), 90000)),
            ]);
        } finally { if (qz.websocket.isActive()) await qz.websocket.disconnect(); }
    });
    assert.equal(typeof result.version, 'string');
    assert(Array.isArray(result.printers));
    report.nativeConnection = 'PASS'; report.version = result.version; report.printerCount = result.printers.length;
} catch {
    report.nativeConnection = 'BLOCKED';
    report.reason = 'Koneksi/izin QZ belum selesai. Periksa QZ Tray, trust WSS dan prompt izin pada PC; tidak bypass TLS.';
    process.exitCode = 1;
} finally {
    await browser?.close();
    await writeFile(path.join(directory, 'native-smoke.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ ...report, artifact: path.relative(root, directory), browserClosed: true }));
}
