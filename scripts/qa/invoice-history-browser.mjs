import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `parties-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const socket = createServer(); socket.once('error', reject); socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, APP_TIMEZONE: 'UTC', DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `history_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const php = (file, args = [], input = '') => {
    const result = spawnSync('php', [file, ...args], { cwd: root, env, input, encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, result.stderr); return result.stdout;
};
php('tests/Browser/parties-fixture.php', [], JSON.stringify({ password, inventory_demo: true, checkout_demo: true }));
const fixture = JSON.parse(php('tests/Browser/invoice-history-data.php', ['--seed'], JSON.stringify({ password })));
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
let browser; let page;
const checks = []; const errors = []; const expectedNetwork = [];
try {
    for (let i = 0; i < 80; i++) { try { if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(5000) })).ok) break; } catch { /* Await isolated server. */ } await new Promise((resolve) => setTimeout(resolve, 250)); }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, timezoneId: 'Asia/Jakarta' });
    const attach = async (context) => {
        await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        await context.addInitScript(() => { window.__qaPrints = 0; window.print = () => { window.__qaPrints++; }; });
        const target = await context.newPage();
        target.on('pageerror', (error) => errors.push(error.message));
        target.on('console', (message) => {
            if (!['error', 'warning'].includes(message.type())) return;
            if (/status of (403|503)/.test(message.text())) expectedNetwork.push(message.text()); else errors.push(message.text());
        });
        return target;
    };
    const login = async (target, role) => {
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password); await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard');
    };
    const wait = async () => { await page.locator('section[aria-busy="false"]').waitFor(); };
    const shot = async (name) => {
        await page.locator('[role="dialog"][data-state="closed"]').waitFor({ state: 'detached' });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        const dialog = page.locator('[role="dialog"][data-state="open"]');
        if (await dialog.isVisible()) { const box = await dialog.boundingBox(); const v = page.viewportSize(); assert(box && box.x >= 0 && box.y >= 0 && box.x + box.width <= v.width + 1 && box.y + box.height <= v.height + 1, `${name}: dialog bounds`); }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: framing PASS`);
    };
    const apply = async () => { await page.getByRole('button', { name: 'Terapkan', exact: true }).click(); await wait(); };
    const reset = async () => { await page.getByRole('button', { name: 'Reset filter', exact: true }).click(); await wait(); };
    const choose = async (label, name) => { await page.getByLabel(label, { exact: true }).click(); await page.getByRole('option', { name, exact: true }).click(); };
    page = await attach(context); await login(page, 'checkout');
    await page.getByRole('link', { name: 'Riwayat Penjualan', exact: true }).click(); await page.waitForURL('**/sales/invoices');
    await page.getByRole('heading', { name: 'Riwayat penjualan', exact: true }).waitFor();
    await page.getByText('27 penjualan - Halaman 1 dari 2', { exact: true }).waitFor(); assert.equal(await page.getByTestId('invoice-row').count(), 25);
    await page.getByRole('button', { name: 'Halaman berikutnya', exact: true }).click(); await page.getByText('27 penjualan - Halaman 2 dari 2', { exact: true }).waitFor();
    assert.equal(await page.getByTestId('invoice-row').count(), 2); await page.reload(); assert.equal(await page.getByTestId('invoice-row').count(), 2);
    await reset();
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click(); await page.keyboard.press('Escape');
    await page.getByRole('menu').waitFor({ state: 'hidden' }); await page.waitForFunction(() => document.activeElement?.textContent?.includes('Shortcut'));
    await page.keyboard.press('Alt+f'); assert(await page.getByLabel('Nomor invoice', { exact: true }).evaluate((element) => element === document.activeElement));
    await page.getByLabel('Nomor invoice', { exact: true }).fill(fixture.first.number.toLowerCase()); await page.keyboard.press('Control+Enter');
    await page.getByText('1 penjualan - Halaman 1 dari 1', { exact: true }).waitFor();
    assert.equal(await page.getByTestId('invoice-row').count(), 1); await page.reload(); assert.equal(await page.getByTestId('invoice-row').count(), 1);
    await reset(); await page.getByLabel('Tanggal awal (WIB)', { exact: true }).fill('2026-10-16'); await page.getByLabel('Tanggal akhir (WIB)', { exact: true }).fill('2026-10-16'); await apply();
    await page.getByText('2 penjualan - Halaman 1 dari 1', { exact: true }).waitFor();
    await choose('Pelanggan', 'Installer CCTV Demo'); await choose('Gudang', 'Gudang Utama'); await apply();
    await page.getByText('1 penjualan - Halaman 1 dari 1', { exact: true }).waitFor();
    assert(!(await page.getByTestId('invoice-row').innerText()).includes('Master changed'));
    await page.getByLabel('Nomor invoice', { exact: true }).fill('not-an-invoice'); await apply(); await page.getByText('Tidak ada penjualan ditemukan.', { exact: true }).waitFor();
    await reset(); checks.push('Own scope/count/options, pagination/reload, search/shortcuts, WIB dates, historical filters and empty/reset PASS');
    await page.keyboard.press('Control+k'); await page.getByRole('dialog', { name: 'Command Palette' }).waitFor();
    await page.getByPlaceholder('Cari menu atau operasi...').fill('Riwayat'); await page.getByRole('button', { name: /Riwayat Penjualan/ }).click(); await wait();
    await shot('history-desktop');
    await page.locator('[data-sidebar="trigger"]').click(); await shot('history-collapsed');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('history-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('history-portrait');
    const latestButton = page.getByRole('button', { name: `Lihat ${fixture.latest.number}`, exact: true });
    await page.route(`**/pos/invoices/${fixture.latest.id}`, (route) => route.fulfill({ status: 503, contentType: 'application/json', body: '{}' }), { times: 1 });
    await latestButton.click(); await page.getByRole('dialog').getByRole('alert').waitFor();
    assert(await page.getByRole('button', { name: 'Cetak', exact: true }).isDisabled());
    await page.getByRole('button', { name: 'Muat ulang bukti', exact: true }).click(); await page.getByRole('dialog').getByText(fixture.latest.number, { exact: true }).waitFor();
    await page.getByRole('dialog').getByText('Kamera IP 2MP', { exact: true }).waitFor();
    await shot('history-receipt-portrait'); await page.keyboard.press('Control+p'); assert.equal(await page.evaluate(() => window.__qaPrints), 1);
    await page.emulateMedia({ media: 'print' }); assert(await page.locator('.pos-receipt-print').isVisible()); assert(!(await page.locator('#app').isVisible()));
    await page.screenshot({ path: path.join(artifacts, 'history-print.png'), fullPage: true }); await page.emulateMedia({ media: 'screen' }); await page.keyboard.press('Escape');
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    let release; let started; const held = new Promise((resolve) => { release = resolve; }); const intercepted = new Promise((resolve) => { started = resolve; });
    await page.route(`**/pos/invoices/${fixture.latest.id}`, async (route) => { const response = await route.fetch(); started(); await held; await route.fulfill({ response }).catch(() => {}); }, { times: 1 });
    await latestButton.click(); await intercepted; await page.getByRole('button', { name: 'Selesai', exact: true }).click();
    const previousId = await page.getByTestId('invoice-row').nth(1).getByRole('button').getAttribute('aria-label');
    await page.getByRole('button', { name: previousId, exact: true }).click(); release();
    await page.getByRole('dialog').getByText(previousId.slice(6), { exact: true }).waitFor();
    await page.keyboard.press('Escape'); await page.getByRole('dialog').waitFor({ state: 'hidden' });
    checks.push('Receipt 503/reload, authorized snapshot, keyboard print-only receipt and stale detail cancellation PASS');
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await shot('history-dark-desktop'); await page.setViewportSize({ width: 1024, height: 768 }); await shot('history-dark-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('history-dark-portrait');
    await latestButton.click(); await page.getByRole('dialog').getByText(fixture.latest.number, { exact: true }).waitFor(); await shot('history-dark-receipt');
    await page.keyboard.press('Escape');
    const ownerData = await context.request.get(`${url}/sales/invoices/data`); assert.equal(ownerData.status(), 200);
    const own = await ownerData.json(); assert.equal(own.records.meta.total, 27); assert.equal(own.warehouseOptions.length, 1); assert.equal(own.customerOptions.length, 2);
    assert.equal((await context.request.get(`${url}/pos/invoices/${fixture.other.id}`)).status(), 403);
    for (const role of ['viewer', 'cashier', 'outsider', 'other']) {
        const peer = await browser.newContext({ viewport: { width: 1440, height: 1000 } }); const target = await attach(peer); await login(target, role);
        const response = await peer.request.get(`${url}/sales/invoices/data`);
        if (role === 'viewer' || role === 'other') {
            assert.equal(response.status(), 200); const data = await response.json(); assert.equal(data.records.meta.total, role === 'viewer' ? 28 : 1);
            await target.goto(`${url}/sales/invoices`); await target.getByRole('heading', { name: 'Riwayat penjualan', exact: true }).waitFor();
            assert.equal((await peer.request.get(`${url}/pos/invoices/${fixture.first.id}`)).status(), role === 'viewer' ? 200 : 403);
            assert.equal(data.warehouseOptions.length, role === 'viewer' ? 2 : 1);
        } else {
            assert.equal(response.status(), 403);
            assert.equal((await peer.request.get(`${url}/sales/invoices`)).status(), 403);
            assert.equal(await target.getByRole('link', { name: 'Riwayat Penjualan', exact: true }).count(), 0);
        }
        await peer.close();
    }
    checks.push('Viewer cross-owner, peer own-only, create-only/outsider denied and navigation guarded PASS');
    const after = JSON.parse(php('tests/Browser/invoice-history-data.php'));
    assert.equal(after.fingerprint, fixture.fingerprint); assert.equal(after.invoices, 28); assert.deepEqual(errors, []);
    const report = { status: 'PASS', checks, console: errors, expectedNetwork, persisted: { invoices: after.invoices, fingerprintUnchanged: true }, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'invoice-history-report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'history-failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'invoice-history-report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
