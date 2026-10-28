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
const artifacts = path.join(root, 'storage/framework/testing', `cashbook-browser-${id}`);
const database = path.join(root, 'storage/framework/testing', `parties-browser-${id}.sqlite`);
await mkdir(path.join(artifacts, 'framework/views'), { recursive: true });
await mkdir(path.join(artifacts, 'framework/cache'), { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const server = createServer(); server.once('error', reject); server.listen(0, '127.0.0.1', () => { const port = server.address().port; server.close(() => resolve(port)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    APP_CONFIG_CACHE: path.join(artifacts, 'config.php'), APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'), LARAVEL_STORAGE_PATH: artifacts,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `cashbook_qa_${id}`,
    SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], { cwd: root, env, input: JSON.stringify({ password, inventory_demo: true, checkout_demo: true }), encoding: 'utf8', windowsHide: true });
assert.equal(fixture.status, 0, fixture.stderr);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const checks = []; const errors = []; const requests = [];
let browser; let page; let expectedAbort = false;
try {
    for (let i = 0; i < 80; i++) { try { const response = await fetch(`${url}/login`); await response.text(); if (response.ok) break; } catch { /* Wait for isolated server. */ } await new Promise((resolve) => setTimeout(resolve, 250)); }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error' && !(expectedAbort && /ERR_FAILED/.test(message.text()))) errors.push(message.text()); });
    page.on('request', (request) => { if (request.method() === 'POST' && request.url() === `${url}/finance/cashbook/entries`) requests.push(request.postDataJSON()); });
    const choose = async (label, name) => { await page.getByLabel(label, { exact: true }).click(); await page.getByRole('option', { name, exact: true }).click(); };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        if (await page.getByRole('dialog').isVisible()) { const box = await page.getByRole('dialog').boundingBox(); const size = page.viewportSize(); assert(box && box.x >= 0 && box.y >= 0 && box.x + box.width <= size.width + 1 && box.y + box.height <= size.height + 1, `${name}: dialog framing`); }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
    };
    const closing = async (amount) => { await page.getByTestId('cashbook-closing').filter({ hasText: amount }).waitFor(); assert.equal(await page.getByTestId('cashbook-closing').innerText(), amount); };
    const saved = async () => { await page.getByRole('dialog').waitFor({ state: 'detached' }); };
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-manager@example.test'); await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click(); await page.waitForURL('**/dashboard');
    await page.goto(`${url}/finance/cashbook`); await page.getByRole('heading', { name: 'Buku Kas', exact: true }).waitFor();
    await choose('Akun kas/bank', 'CASH-DEMO - Kas Toko Demo'); await page.getByRole('button', { name: 'Tampilkan', exact: true }).click();
    await page.getByText('Saldo awal akun ini belum ditetapkan.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Saldo awal', exact: true }).click();
    await page.getByLabel('Nominal (Rp)', { exact: true }).fill('500.000');
    await page.getByRole('button', { name: 'Tetapkan saldo awal', exact: true }).click(); await saved(); await closing('Rp 500.000');
    await page.getByRole('button', { name: 'Saldo awal', exact: true }).click();
    await choose('Akun', 'BANK-DEMO - Bank Operasional Demo'); await page.getByLabel('Nominal (Rp)', { exact: true }).fill('1.000.000');
    await page.getByRole('button', { name: 'Tetapkan saldo awal', exact: true }).click(); await saved();
    await page.getByRole('button', { name: 'Catat mutasi', exact: true }).click(); await choose('Jenis mutasi', 'Pengeluaran operasional');
    await page.getByLabel('Nominal (Rp)', { exact: true }).fill('700.000'); await page.getByLabel('Keterangan', { exact: true }).fill('Biaya pemasangan CCTV');
    await page.getByRole('dialog').getByRole('button', { name: 'Catat mutasi', exact: true }).click(); await saved(); await closing('Rp -200.000');
    await page.getByRole('alert').filter({ hasText: 'Saldo tercatat minus' }).waitFor(); await shot('negative-desktop');
    checks.push('Explicit opening per account, Indonesian input and negative warning without blocking PASS');
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.getByRole('button', { name: 'Catat mutasi', exact: true }).click(); await choose('Jenis mutasi', 'Transfer antar-akun');
    await choose('Akun tujuan', 'BANK-DEMO - Bank Operasional Demo'); await page.getByLabel('Nominal (Rp)', { exact: true }).fill('100.000'); await page.getByLabel('Keterangan', { exact: true }).fill('Setoran kas ke bank');
    await shot('transfer-tablet'); await page.getByRole('dialog').getByRole('button', { name: 'Catat mutasi', exact: true }).click(); await saved(); await closing('Rp -300.000');
    const outgoing = page.getByRole('row').filter({ hasText: 'Biaya pemasangan CCTV' });
    await outgoing.getByRole('button', { name: /^Balik BK-/ }).click(); await page.getByLabel('Alasan pembalikan', { exact: true }).fill('Koreksi biaya salah');
    await page.getByRole('button', { name: 'Balik mutasi', exact: true }).click(); await saved(); await closing('Rp 400.000');
    assert.equal(await page.getByRole('row').filter({ hasText: 'Biaya pemasangan CCTV' }).getByRole('button').count(), 0);
    await shot('cashbook-tablet'); checks.push('Transfer and audited reversal retain original row and carry balance PASS');
    await choose('Akun kas/bank', 'BANK-DEMO - Bank Operasional Demo'); await page.getByRole('button', { name: 'Tampilkan', exact: true }).click(); await closing('Rp 1.100.000');
    await choose('Akun kas/bank', 'CASH-DEMO - Kas Toko Demo'); await page.getByRole('button', { name: 'Tampilkan', exact: true }).click(); await closing('Rp 400.000');
    expectedAbort = true;
    await page.route('**/finance/cashbook/entries', async (route) => { assert.equal((await route.fetch()).status(), 200); await route.abort('failed'); }, { times: 1 });
    await page.getByRole('button', { name: 'Catat mutasi', exact: true }).click(); await page.getByLabel('Nominal (Rp)', { exact: true }).fill('1.000,123456'); await page.getByLabel('Keterangan', { exact: true }).fill('Penerimaan operasional recovery');
    await page.getByRole('dialog').getByRole('button', { name: 'Catat mutasi', exact: true }).click();
    await page.getByText('Status belum terkonfirmasi. Tutup dialog lalu ulangi pengiriman yang sama.', { exact: true }).waitFor();
    assert(await page.getByLabel('Nominal (Rp)', { exact: true }).isDisabled());
    await page.reload(); await page.getByRole('button', { name: 'Ulangi pengiriman yang sama', exact: true }).click(); await closing('Rp 401.000,123456');
    await page.getByRole('button', { name: 'Ulangi pengiriman yang sama', exact: true }).waitFor({ state: 'detached' });
    assert.equal(requests.at(-1).idempotency_key, requests.at(-2).idempotency_key);
    const history = await page.request.get(`${url}/finance/cashbook/data?${new URLSearchParams({ account_id: requests.at(-1).account_id })}`);
    const statement = await history.json();
    assert.equal(statement.rows.filter((row) => row.description === 'Penerimaan operasional recovery').length, 1);
    checks.push('Lost response after commit, reload and same-key retry create one exact6 movement PASS');
    await shot('recovered-tablet');
    assert.deepEqual(errors, []);
    const report = { status: 'PASS', checks, errors, artifacts }; await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, errors, message: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); await new Promise((resolve) => { if (server.exitCode !== null) resolve(); else server.once('exit', resolve); }); }
