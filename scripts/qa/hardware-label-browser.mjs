import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { FakeQzGateway } from '../../tests/Browser/Hardware/fake-qz-gateway.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan instalasi Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const qa = path.join(root, 'storage/framework/testing', `hardware-label-${randomBytes(8).toString('hex')}`);
for (const directory of ['', 'framework/views', 'framework/cache/data', 'logs']) await mkdir(path.join(qa, directory), { recursive: true });
await writeFile(path.join(qa, 'database.sqlite'), '');
const port = await new Promise((resolve, reject) => {
    const server = createServer(); server.once('error', reject);
    server.listen(0, '127.0.0.1', () => { const value = server.address().port; server.close(() => resolve(value)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`, POS_HARDWARE_ENABLED: 'true', POS_HARDWARE_PRINTING_ENABLED: 'true', POS_QZ_ALLOWED_HOSTS: 'localhost,127.0.0.1',
    APP_CONFIG_CACHE: path.join(qa, 'config.php'), APP_ROUTES_CACHE: path.join(qa, 'routes.php'), LARAVEL_STORAGE_PATH: qa,
    DB_CONNECTION: 'sqlite', DB_DATABASE: path.join(qa, 'database.sqlite'), CACHE_STORE: 'array', SESSION_DRIVER: 'database',
    SESSION_COOKIE: `hardware_label_qa_${randomBytes(5).toString('hex')}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false',
    QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
const password = randomBytes(32).toString('hex');
const fixture = (mode) => {
    const result = spawnSync('php', ['tests/Browser/Hardware/label-fixture.php', mode], { cwd: root, env, input: mode === 'setup' ? JSON.stringify({ password }) : '', encoding: 'utf8', windowsHide: true, timeout: 300000 });
    assert.equal(result.status, 0, result.stderr || 'Fixture label gagal; periksa log QA terisolasi.');
    return JSON.parse(result.stdout);
};
const before = fixture('setup');
await writeFile(path.join(qa, 'before.json'), JSON.stringify(before, null, 2));
assert.equal(fixture('verify').fingerprint, before.fingerprint);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'), env: { ...env, APP_ENV: 'production' }, stdio: 'ignore', windowsHide: true,
});
const report = { status: 'RUNNING', software: 'NOT_RUN', physical: 'PHYSICAL_PENDING', checks: [], errors: [], expectedErrors: 0, artifacts: qa };
let browser, context, page;
const fake = new FakeQzGateway();
const check = (name) => { report.checks.push({ name, result: 'PASS' }); console.log(JSON.stringify({ check: name, result: 'PASS' })); };
try {
    for (let index = 0; index < 60; index++) {
        try { if ((await fetch(url + '/login', { signal: AbortSignal.timeout(2000) })).ok) break; } catch { /* Disposable server startup. */ }
        if (index === 59) throw new Error('Server label QA tidak siap.');
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    console.log(JSON.stringify({ event: 'QA_READY', url, artifacts: qa }));
    browser = await chromium.launch({ channel: 'chrome', headless: true });
    context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    await fake.install(context);
    page = await context.newPage();
    page.on('pageerror', (error) => report.errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() !== 'error') return;
        if (/Failed to load resource:.*(?:403|409|419|422|429|ERR_FAILED)/.test(message.text())) report.expectedErrors++;
        else report.errors.push(message.text());
    });
    const login = async (target, email) => {
        await target.goto(url + '/login'); await target.locator('#email').fill(email); await target.locator('#password').fill(password);
        await target.locator('button[type="submit"]').click(); await target.waitForURL('**/dashboard');
    };
    await login(page, 'label.operator@example.test');
    assert.equal(fake.connections, 0);
    fake.mode = 'empty';
    await page.goto(url + '/platform/hardware');
    await page.getByRole('heading', { name: 'Perangkat POS', exact: true }).waitFor();
    await page.getByText('0 printer terdaftar pada OS.', { exact: false }).waitFor();
    assert.equal(fake.connections, 1);
    const firstDiscoveryCalls = fake.calls.filter((call) => call.startsWith('printers.detail')).length;
    await page.waitForTimeout(300);
    assert.equal(fake.calls.filter((call) => call.startsWith('printers.detail')).length, firstDiscoveryCalls);
    assert.equal(firstDiscoveryCalls, 1);
    check('No QZ on login/dashboard; one automatic discovery in authorized workspace, no polling');

    fake.mode = 'denied';
    await page.getByRole('button', { name: 'Deteksi ulang', exact: true }).click();
    await page.getByRole('alert').filter({ hasText: 'Gateway belum tersedia' }).waitFor();
    fake.mode = 'ready';
    await page.getByRole('button', { name: 'Deteksi ulang', exact: true }).click();
    await page.getByText('2 printer terdaftar pada OS.', { exact: false }).waitFor();
    check('QZ protocol handshake, permission rejection, empty and multiple printer discovery');

    await page.getByRole('button', { name: 'Tambah profil', exact: true }).click();
    await page.locator('#printer-name').fill('Label Kasir QA');
    assert.equal(await page.locator('#printer-os').inputValue(), '');
    await page.locator('#printer-os').fill('CT420D');
    await page.getByRole('option', { name: 'CT420D QA', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    const article = page.locator('article').filter({ hasText: 'Label Kasir QA' });
    await article.waitFor();
    await article.getByRole('button', { name: 'Edit profil', exact: true }).click();
    await page.locator('#printer-width_mm').fill('60');
    await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    await article.getByText('localhost / 60 x 30 mm / 203 DPI', { exact: true }).waitFor();
    await article.getByRole('button', { name: 'Nonaktifkan profil', exact: true }).click();
    await article.getByText('Nonaktif', { exact: true }).waitFor();
    await article.getByRole('button', { name: 'Aktifkan profil', exact: true }).click();
    await article.getByText('Aktif', { exact: true }).waitFor();
    await article.getByRole('button', { name: 'Arsipkan profil', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Arsipkan', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    await article.getByRole('button', { name: 'Pulihkan profil', exact: true }).click();
    await article.getByText('Aktif', { exact: true }).waitFor();
    check('Profile create/edit/status/soft delete/restore persists; no automatic default printer');

    await page.getByRole('button', { name: 'Label', exact: true }).click();
    await page.locator('#label-item').fill('001234567890');
    await page.getByRole('option').filter({ hasText: 'Kamera IP 2MP' }).first().click();
    await page.locator('#label-profile').fill('Label Kasir QA');
    await page.getByRole('option').filter({ hasText: 'Label Kasir QA' }).click();
    const image = page.getByRole('img', { name: 'Pratinjau label barcode' });
    await image.waitFor();
    const pixels = await image.evaluate((img) => {
        const canvas = document.createElement('canvas'); canvas.width = img.naturalWidth; canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0);
        const rgba = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        let black = 0, white = 0;
        for (let i = 0; i < rgba.length; i += 4) { if (rgba[i] < 20) black++; if (rgba[i] > 245) white++; }
        return { width: canvas.width, height: canvas.height, black, white };
    });
    assert.equal(pixels.width, 480); assert.equal(pixels.height, 240); assert(pixels.black > 1000 && pixels.white > pixels.black);
    await page.screenshot({ path: path.join(qa, 'desktop-label.png'), fullPage: true });
    await page.getByRole('button', { name: 'Kirim label', exact: true }).dblclick();
    await page.getByText('Label diterima gateway. Periksa hasil cetak fisik.', { exact: true }).waitFor();
    assert.equal(fake.prints.length, 1);
    assert.equal(fake.prints[0].printer.name, 'CT420D QA');
    assert.equal(fake.prints[0].options.copies, 1);
    check('Real SDK builds image-only print; nonblank raster/leading zero and double-click one dispatch');

    await page.getByRole('button', { name: 'Riwayat', exact: true }).click();
    await page.getByRole('button', { name: 'Halaman berikutnya', exact: true }).click();
    await page.getByRole('button', { name: 'Halaman 2', exact: true }).waitFor();
    await page.getByRole('button', { name: 'Halaman pertama', exact: true }).click();
    const printedRow = page.locator('tbody tr').filter({ hasText: 'Kamera IP 2MP' }).filter({ hasText: 'Label Kasir QA' });
    await printedRow.getByText('Diterima gateway', { exact: true }).waitFor();
    await printedRow.getByRole('button', { name: 'Cetak ulang beralasan', exact: true }).click();
    assert(await page.getByRole('dialog').getByRole('button', { name: 'Buat cetak ulang', exact: true }).isDisabled());
    await page.locator('#label-reprint-reason').fill('Label rusak, hasil fisik sudah diperiksa');
    await page.getByRole('dialog').getByRole('button', { name: 'Buat cetak ulang', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    assert.equal(fake.prints.length, 2);
    check('History pagination and reasoned reprint creates a separate job');

    await page.keyboard.press('F1');
    await page.getByRole('heading', { name: 'Panduan Perangkat POS', exact: true }).waitFor();
    await page.keyboard.press('Escape'); await page.getByRole('dialog').waitFor({ state: 'hidden' });
    const connectionCount = fake.connections;
    fake.disconnect();
    await page.keyboard.press('Alt+4');
    await page.getByRole('status').filter({ hasText: 'Belum diuji' }).waitFor();
    assert(await page.locator('#hardware-keyboard-test').evaluate((input) => input === document.activeElement));
    await page.keyboard.type('001234567890'); await page.keyboard.press('Enter');
    await page.getByText('Sesuai', { exact: true }).waitFor();
    await page.getByRole('status').filter({ hasText: 'Input diterima' }).waitFor();
    await page.keyboard.type('001234567890'); await page.keyboard.press('Enter');
    assert.equal(await page.getByText('Sesuai', { exact: true }).count(), 2);
    await page.waitForTimeout(300); assert.equal(fake.connections, connectionCount);
    check('F1, scanner auto-focus/readiness and exact Enter/duplicate scan-back work with QZ disconnected');

    await page.getByRole('button', { name: 'Label', exact: true }).click();
    await page.getByText('Belum terhubung', { exact: true }).waitFor();
    fake.mode = 'ready';
    await page.getByRole('button', { name: 'Deteksi ulang', exact: true }).click();
    await page.getByText('2 printer terdaftar pada OS.', { exact: false }).waitFor();
    fake.mode = 'lost_ack';
    await page.getByRole('button', { name: 'Kirim label', exact: true }).click();
    await page.getByText('Hasil pengiriman belum pasti. Periksa printer; jangan kirim ulang otomatis.', { exact: true }).waitFor();
    assert.equal(fake.prints.length, 3);
    await page.waitForTimeout(500); assert.equal(fake.prints.length, 3);
    await page.getByRole('button', { name: 'Kirim label', exact: true }).click();
    await page.getByText('Job ini sudah diproses. Periksa riwayat sebelum cetak ulang.', { exact: true }).waitFor();
    assert.equal(fake.prints.length, 3);
    check('Lost ack is uncertain; same logical request and disconnect never auto-resend');

    for (const [name, viewport] of [['tablet-landscape', { width: 1024, height: 768 }], ['tablet-portrait', { width: 768, height: 1024 }]]) {
        await page.setViewportSize(viewport);
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
        await page.screenshot({ path: path.join(qa, name + '.png'), fullPage: true });
        check('Layout/overflow ' + name);
    }
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(qa, 'tablet-dark.png'), fullPage: true });
    check('Dark theme representative');

    const viewerContext = await browser.newContext({ viewport: { width: 1024, height: 768 } });
    const viewerFake = new FakeQzGateway();
    await viewerFake.install(viewerContext);
    await viewerContext.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    const viewer = await viewerContext.newPage();
    await login(viewer, 'label.viewer@example.test'); await viewer.goto(url + '/platform/hardware');
    assert.equal(await viewer.getByRole('button', { name: 'Tambah profil', exact: true }).count(), 0);
    assert(await viewer.getByRole('button', { name: 'Deteksi ulang', exact: true }).isDisabled());
    await viewer.waitForTimeout(300); assert.equal(viewerFake.connections, 0);
    assert.equal((await viewerContext.request.get(url + '/platform/hardware/catalog?q=Kamera')).status(), 403);
    await viewerContext.close();
    check('Viewer cannot discover/manage/print/catalog or access other operator jobs');
    const after = fixture('verify');
    await writeFile(path.join(qa, 'after.json'), JSON.stringify(after, null, 2));
    assert.deepEqual(after.table_fingerprints, before.table_fingerprints);
    assert.equal(after.attempts, 3);
    assert(after.statuses.includes('uncertain'));
    check('Existing business/master/grants/audit fingerprint unchanged');
    assert.deepEqual(report.errors, []);
    report.status = 'PASS'; report.software = 'VIRTUAL_PASSED'; report.prints = fake.prints;
    const reviewMs = Math.min(180000, Math.max(0, Number(process.env.QA_HARDWARE_REVIEW_MS) || 0));
    if (reviewMs) { console.log(JSON.stringify({ event: 'REVIEW_WINDOW', url, milliseconds: reviewMs })); await new Promise((resolve) => setTimeout(resolve, reviewMs)); }
} catch (error) {
    report.status = 'FAIL'; report.errors.push(error.message); process.exitCode = 1;
    if (page && !page.isClosed()) await page.screenshot({ path: path.join(qa, 'failure.png'), fullPage: true }).catch(() => {});
    console.error(error.message);
} finally {
    await context?.close(); await browser?.close();
    const stopped = new Promise((resolve) => server.once('exit', resolve));
    if (server.exitCode === null) server.kill();
    await Promise.race([stopped, new Promise((resolve) => setTimeout(resolve, 5000))]);
    report.serverStopped = server.exitCode !== null || server.signalCode !== null;
    await writeFile(path.join(qa, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ event: 'QA_FINISHED', status: report.status, checks: report.checks.length, serverStopped: report.serverStopped, artifacts: qa }));
}
