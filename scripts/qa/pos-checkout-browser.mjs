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
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `pos_checkout_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], { cwd: root, env, input: JSON.stringify({ password, inventory_demo: true, checkout_demo: true }), encoding: 'utf8', windowsHide: true });
assert.equal(fixture.status, 0, fixture.stderr);
const verify = (...args) => {
    const result = spawnSync('php', ['tests/Browser/pos-checkout-verify.php', ...args], { cwd: root, env, encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, result.stderr);
    return args.length ? result.stdout : JSON.parse(result.stdout);
};
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
let browser; let page;
let expectedAbort = false;
const errors = []; const checks = []; const expectedNetwork = [];
try {
    for (let i = 0; i < 80; i++) { try { if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(5000) })).ok) break; } catch { /* Await isolated server. */ } await new Promise((resolve) => setTimeout(resolve, 250)); }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const attach = async (context) => {
        await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        await context.addInitScript(() => { window.__qaPrints = 0; window.print = () => { window.__qaPrints++; }; });
        const target = await context.newPage();
        target.on('pageerror', (error) => errors.push(error.message));
        target.on('console', (message) => {
            if (!['error', 'warning'].includes(message.type())) return;
            if (/status of (422|503)/.test(message.text()) || (expectedAbort && /net::ERR_FAILED/.test(message.text()))) expectedNetwork.push(message.text());
            else errors.push(message.text());
        });
        return target;
    };
    const login = async (target, role) => {
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password); await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard');
        await target.goto(`${url}/pos`); await target.getByRole('heading', { name: 'POS', exact: true }).waitFor();
    };
    const choose = async (target, label, name) => { await target.getByLabel(label, { exact: true }).click(); await target.getByRole('option', { name, exact: true }).click(); };
    const prepare = async (target, quantity = '1') => {
        await choose(target, 'Gudang', 'Gudang Utama'); await choose(target, 'Pelanggan', 'Installer CCTV Demo');
        await target.getByRole('button', { name: 'Tambah Kamera IP 2MP', exact: true }).click();
        await target.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).fill(quantity);
        await target.getByRole('button', { name: 'Periksa keranjang', exact: true }).click();
        await target.getByTestId('cart-subtotal').filter({ hasText: 'Rp' }).waitFor();
    };
    const shot = async (name) => {
        await page.locator('[role="dialog"][data-state="closed"]').waitFor({ state: 'detached' });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        const dialog = page.locator('[role="dialog"][data-state="open"]');
        if (await dialog.isVisible()) { const box = await dialog.boundingBox(); const viewport = page.viewportSize(); assert(box && box.x >= 0 && box.y >= 0 && box.x + box.width <= viewport.width + 1 && box.y + box.height <= viewport.height + 1, `${name}: dialog bounds`); }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: framing PASS`);
    };
    page = await attach(context); await login(page, 'checkout'); await prepare(page, '2');
    await page.route('**/pos/payment-methods', (route) => route.fulfill({ status: 503, contentType: 'application/json', body: '{}' }), { times: 1 });
    await page.keyboard.press('F8');
    await page.getByRole('button', { name: 'Muat ulang metode', exact: true }).click();
    await page.getByLabel('Nominal 1', { exact: true }).waitFor();
    await page.getByLabel('Nominal 1', { exact: true }).fill('50.000');
    await page.getByRole('button', { name: 'Tambah metode', exact: true }).click();
    await choose(page, 'Metode 2', 'Transfer'); await page.getByLabel('Nominal 2', { exact: true }).fill('100.001');
    assert(await page.getByRole('button', { name: 'Bayar dan posting', exact: true }).isDisabled());
    assert.equal(await page.getByTestId('payment-change').innerText(), 'Rp 0');
    await page.getByLabel('Nominal 2', { exact: true }).fill('60.000');
    assert.equal(await page.getByTestId('payment-change').innerText(), 'Rp 10.000');
    await shot('payment-desktop');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('payment-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('payment-portrait');
    let requests = 0; page.on('request', (request) => { if (request.method() === 'POST' && request.url() === `${url}/pos/invoices`) requests++; });
    let release; const gate = new Promise((resolve) => { release = resolve; });
    await page.route('**/pos/invoices', async (route) => { await gate; await route.continue(); }, { times: 1 });
    await page.getByRole('button', { name: 'Bayar dan posting', exact: true }).click();
    await page.getByRole('button', { name: 'Memproses...', exact: true }).waitFor();
    assert(await page.getByRole('button', { name: 'Memproses...', exact: true }).isDisabled());
    await page.keyboard.press('Control+Enter'); await page.keyboard.press('Control+Enter'); assert.equal(requests, 1); release();
    await page.getByRole('heading', { name: 'Bukti penjualan', exact: true }).waitFor();
    const first = page.getByRole('dialog', { name: 'Bukti penjualan', exact: true });
    await first.getByText('Rp 10.000', { exact: true }).waitFor(); await shot('receipt-portrait');
    await page.setViewportSize({ width: 1440, height: 1000 }); await shot('receipt-desktop'); await page.setViewportSize({ width: 768, height: 1024 });
    assert.equal(verify().invoices, 1); assert.equal(verify().records, 2);
    await first.getByRole('button', { name: 'Cetak', exact: true }).click(); assert.equal(await page.evaluate(() => window.__qaPrints), 1);
    await page.emulateMedia({ media: 'print' });
    assert(await page.locator('.pos-receipt-print').isVisible()); assert(!(await page.locator('main').isVisible()));
    await page.screenshot({ path: path.join(artifacts, 'receipt-print.png'), fullPage: true }); await page.emulateMedia({ media: 'screen' });
    await first.getByRole('button', { name: 'Selesai', exact: true }).click();
    await page.reload(); await page.getByRole('button', { name: 'Bukti terakhir', exact: true }).click();
    await page.getByRole('dialog').getByText('Rp 10.000', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Muat ulang bukti', exact: true }).click(); await page.getByRole('dialog').getByText('Rp 10.000', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Selesai', exact: true }).click();
    checks.push('Multi-payment cash net/change, double submit, receipt GET/reload and isolated print PASS');

    await prepare(page);
    await page.keyboard.press('F8'); await page.getByLabel('Nominal 1', { exact: true }).waitFor();
    expectedAbort = true; let committedId; const payloads = [];
    page.on('request', (request) => { if (request.method() === 'POST' && request.url() === `${url}/pos/invoices`) payloads.push(request.postDataJSON()); });
    await page.route('**/pos/invoices', async (route) => { const response = await route.fetch(); assert.equal(response.status(), 201); committedId = (await response.json()).id; await route.abort('failed'); }, { times: 1 });
    await page.getByRole('button', { name: 'Bayar dan posting', exact: true }).click();
    await page.getByRole('alert').filter({ hasText: 'Status pembayaran belum dapat dipastikan' }).waitFor();
    assert(await page.getByLabel('Nominal 1', { exact: true }).isDisabled());
    await page.keyboard.press('Escape'); assert(await page.getByRole('button', { name: 'Ulangi pembayaran', exact: true }).isVisible());
    await shot('payment-unknown');
    page.once('dialog', (dialog) => dialog.accept()); await page.reload();
    await page.getByRole('button', { name: 'Ulangi pembayaran', exact: true }).waitFor();
    await page.getByRole('button', { name: 'Ulangi pembayaran', exact: true }).click();
    await page.getByRole('heading', { name: 'Bukti penjualan', exact: true }).waitFor();
    await page.getByRole('dialog').getByText(`POS-${committedId.toUpperCase()}`, { exact: true }).waitFor();
    assert.deepEqual(payloads[0], payloads[1]); assert.equal(verify().invoices, 2); assert.equal(Number(verify().stock), 5);
    await page.getByRole('button', { name: 'Selesai', exact: true }).click(); expectedAbort = false;
    checks.push('Lost response after commit, frozen attempt, reload and identical-key replay: exactly two invoices total PASS');

    await page.setViewportSize({ width: 1440, height: 1000 });
    await prepare(page); verify('--reprice'); await page.keyboard.press('F8'); await page.getByLabel('Nominal 1', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Bayar dan posting', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    await page.getByRole('alert').filter({ hasText: 'Harga atau konfigurasi berubah' }).waitFor();
    assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '1');
    assert.equal(await page.getByTestId('cart-subtotal').innerText(), '-'); assert.equal(verify().invoices, 2);
    await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click(); await page.getByTestId('cart-subtotal').filter({ hasText: 'Rp 55.000' }).waitFor();
    await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await page.keyboard.press('F8'); await page.getByLabel('Nominal 1', { exact: true }).waitFor();
    await shot('payment-dark-desktop');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('payment-dark-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('payment-dark-portrait');
    await page.getByRole('button', { name: 'Batal', exact: true }).click();
    await page.getByRole('button', { name: 'Bukti terakhir', exact: true }).click();
    await page.getByRole('dialog', { name: 'Bukti penjualan', exact: true }).getByText('Rp 50.000', { exact: true }).first().waitFor();
    await shot('receipt-dark-portrait'); await page.setViewportSize({ width: 1440, height: 1000 }); await shot('receipt-dark-desktop');
    await page.emulateMedia({ media: 'print' });
    assert.equal(await page.evaluate(() => getComputedStyle(document.body).backgroundColor), 'rgb(255, 255, 255)');
    assert.equal(await page.evaluate(() => getComputedStyle(document.body).overflow), 'visible');
    await page.screenshot({ path: path.join(artifacts, 'receipt-dark-print.png'), fullPage: true }); await page.emulateMedia({ media: 'screen' });
    await page.getByRole('button', { name: 'Selesai', exact: true }).click();
    checks.push('Stale quote 422 keeps cart, invalidates quote and does not post; dark desktop/tablet PASS');

    const readonlyContext = await browser.newContext(); const readonly = await attach(readonlyContext); await login(readonly, 'cashier'); await prepare(readonly);
    assert(await readonly.getByRole('button', { name: 'Pembayaran', exact: true }).isDisabled());
    await readonly.keyboard.press('F8'); assert.equal(await readonly.getByRole('dialog').count(), 0); await readonlyContext.close();
    const storageContext = await browser.newContext(); await storageContext.addInitScript(() => {
        const original = Storage.prototype.setItem;
        Storage.prototype.setItem = function (key, value) { if (this === window.sessionStorage && key.startsWith('pos.checkout.') && !key.endsWith('.receipt')) throw new DOMException('QA storage unavailable'); return original.call(this, key, value); };
    });
    const storagePage = await attach(storageContext); await login(storagePage, 'checkout'); await prepare(storagePage); await storagePage.keyboard.press('F8');
    await storagePage.getByLabel('Nominal 1', { exact: true }).waitFor(); await storagePage.getByRole('button', { name: 'Bayar dan posting', exact: true }).click();
    await storagePage.getByRole('alert').filter({ hasText: 'Pembayaran tidak dikirim' }).waitFor(); assert.equal(verify().invoices, 2); await storageContext.close();
    checks.push('Create-only cannot pay; unavailable recovery storage prevents POST PASS');
    assert.deepEqual(errors, []);
    const report = { status: 'PASS', checks, console: errors, expectedNetwork, persisted: verify(), artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'pos-checkout-report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'checkout-failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'pos-checkout-report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
