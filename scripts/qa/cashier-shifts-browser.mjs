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
const artifacts = path.join(root, 'storage/framework/testing', `cashier-shifts-${id}`);
const database = path.join(root, 'storage/framework/testing', `parties-browser-${id}.sqlite`);
await mkdir(path.join(artifacts, 'framework/views'), { recursive: true });
await mkdir(path.join(artifacts, 'framework/cache'), { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const server = createServer(); server.once('error', reject); server.listen(0, '127.0.0.1', () => { const port = server.address().port; server.close(() => resolve(port)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    APP_CONFIG_CACHE: path.join(artifacts, 'config.php'), APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'), LARAVEL_STORAGE_PATH: artifacts,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `shift_qa_${id}`,
    SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], { cwd: root, env, input: JSON.stringify({ password, inventory_demo: true, checkout_demo: true, shift_demo: true }), encoding: 'utf8', windowsHide: true });
assert.equal(fixture.status, 0, fixture.stderr);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const checks = []; const errors = []; const attempts = [];
let browser; let page; let expectedAbort = false;
try {
    for (let i = 0; i < 80; i++) { try { const response = await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) }); await response.text(); if (response.ok) break; } catch { /* Wait for isolated server. */ } await new Promise((resolve) => setTimeout(resolve, 250)); }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error' && !(expectedAbort && /ERR_FAILED/.test(message.text()))) errors.push(message.text()); });
    page.on('request', (request) => { if (request.method() === 'POST' && /\/pos\/shifts\/[^/]+\/open$/.test(request.url())) attempts.push({ url: request.url(), payload: request.postDataJSON() }); });
    const login = async (email) => { await page.goto(`${url}/login`); await page.getByLabel('Email address').fill(email); await page.getByLabel('Password', { exact: true }).fill(password); await page.getByRole('button', { name: 'Log in', exact: true }).click(); await page.waitForURL('**/dashboard'); };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        const dialog = page.getByRole('dialog');
        if (await dialog.isVisible()) { const box = await dialog.boundingBox(); const size = page.viewportSize(); assert(box && box.x >= 0 && box.y >= 0 && box.x + box.width <= size.width + 1 && box.y + box.height <= size.height + 1, `${name}: dialog framing`); }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), animations: 'disabled' });
    };
    await login('qa-checkout@example.test');
    await page.goto(`${url}/pos`);
    await page.getByRole('button', { name: 'Tambah Kamera IP 2MP', exact: true }).waitFor();
    const scan = page.getByLabel('Scan barcode', { exact: true }); await scan.fill('DEMO-CCTV-IP-2MP-UNIT'); await scan.press('Enter');
    await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click(); await page.getByText('Terperiksa', { exact: true }).waitFor();
    assert(await page.getByRole('button', { name: /^Pembayaran/ }).isDisabled());
    await page.getByRole('button', { name: 'Buka shift', exact: true }).click();
    await page.getByLabel('Laci kas', { exact: true }).click(); await page.getByRole('option', { name: 'CASH-DEMO · Kas Toko Demo', exact: true }).click();
    await page.getByText('Rp 500.000', { exact: true }).waitFor(); await shot('open-desktop');
    expectedAbort = true;
    await page.route('**/pos/shifts/*/open', async (route) => { assert.equal((await route.fetch()).status(), 200); await route.abort('failed'); }, { times: 1 });
    await page.getByRole('button', { name: 'Mulai shift', exact: true }).click(); await page.getByRole('button', { name: 'Periksa ulang', exact: true }).waitFor();
    await page.reload(); await page.getByRole('button', { name: 'Periksa ulang', exact: true }).click(); await page.getByRole('dialog').waitFor({ state: 'detached' });
    assert.equal(attempts.length, 2); assert.deepEqual(attempts[0], attempts[1]);
    await page.getByRole('button', { name: 'Shift aktif', exact: true }).waitFor();
    checks.push('No-shift payment guard; explicit opening from Cashbook; lost response/reload/same-key recovery PASS');
    // Reload does not persist an unheld cart; verify dialog alone keeps the current cart.
    await page.getByLabel('Scan barcode', { exact: true }).fill('DEMO-CCTV-IP-2MP-UNIT'); await page.getByLabel('Scan barcode', { exact: true }).press('Enter');
    await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).waitFor();
    for (const name of ['Klasik', 'Visual']) {
        await page.getByRole('radio', { name: `Tampilan ${name}`, exact: true }).click();
        await page.getByRole('button', { name: 'Shift aktif', exact: true }).click(); await page.getByLabel('Uang fisik terhitung', { exact: true }).waitFor();
        await page.getByRole('button', { name: 'Batal', exact: true }).click(); assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '1');
    }
    await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click(); await page.getByText('Terperiksa', { exact: true }).waitFor();
    await page.getByRole('button', { name: /^Pembayaran/ }).click();
    await page.getByRole('button', { name: 'Bayar dan posting', exact: true }).click();
    await page.getByRole('dialog').filter({ hasText: 'Bukti penjualan' }).waitFor();
    await page.keyboard.press('Escape');
    const current = await (await page.request.get(`${url}/pos/shifts/current`)).json(); assert(current.active);
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.getByRole('button', { name: 'Shift aktif', exact: true }).click(); await page.getByLabel('Uang fisik terhitung', { exact: true }).fill('499.999,5'); await page.getByLabel('Alasan selisih', { exact: true }).fill('Selisih uang fisik QA');
    await shot('close-tablet');
    await page.getByRole('button', { name: 'Tutup shift', exact: true }).click(); await page.getByRole('dialog').waitFor({ state: 'detached' });
    assert.equal((await (await page.request.get(`${url}/pos/shifts/current`)).json()).active, null);
    await page.goto(`${url}/sales/cashier-shifts`); await page.getByText('Menunggu review', { exact: true }).waitFor(); await shot('history-tablet');
    const detail = await (await page.request.get(`${url}/pos/shifts/${current.active.id}`)).json();
    assert.equal(detail.shift.opening_balance, '500000.000000'); assert.equal(detail.summary.invoice_count, 1); assert.equal(detail.shift.review_status, 'pending');
    checks.push('Both POS views retain cart across shift dialogs; paid checkout bound to shift; discrepancy closes/releases drawer PASS');
    await context.close();
    const admin = await browser.newContext({ viewport: { width: 1440, height: 1000 } }); await admin.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await admin.newPage(); page.on('pageerror', (error) => errors.push(error.message)); await login('qa-manager@example.test');
    await page.goto(`${url}/sales/cashier-shifts`); await page.getByRole('button', { name: 'Detail shift QA checkout', exact: true }).click();
    await page.getByLabel('Catatan pemeriksaan', { exact: true }).fill('Pemeriksaan supervisor QA'); await shot('review-desktop');
    await page.getByRole('button', { name: 'Sahkan review', exact: true }).click(); await page.getByText('Sudah direview', { exact: true }).waitFor();
    assert.deepEqual(errors, []);
    checks.push('Supervisor review readonly counts and amounts; no automatic Cashbook adjustment; clean console and desktop/tablet layout PASS');
    const report = { status: 'PASS', checks, errors, artifacts }; await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, errors, message: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); await new Promise((resolve) => { if (server.exitCode !== null) resolve(); else server.once('exit', resolve); }); }
