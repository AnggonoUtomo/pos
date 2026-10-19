import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile, unlink } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `return-refund-browser-${id}`);
await mkdir(artifacts, { recursive: true });
const database = `${artifacts}.sqlite`; await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const s = createServer(); s.once('error', reject); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `refund_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = (input) => { const r = spawnSync('php', ['tests/Browser/sale-return-refund-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(r.status, 0, `Fixture refund gagal: ${r.stderr}`); return JSON.parse(r.stdout); };
const password = randomBytes(32).toString('hex');
let browser; let server; let page; let expectedFailure = false;
const errors = []; const checks = []; const unexpectedNetwork = []; const payloads = [];
try {
    const setup = fixture({ password }); console.log('QA refund: fixture siap.');
    server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
    let ready = false;
    for (let i = 0; i < 30; i++) { try { const r = await fetch(`${url}/login`, { signal: AbortSignal.timeout(2500) }); await r.arrayBuffer(); if (r.ok) { ready = true; break; } } catch { /* Wait for QA server. */ } await new Promise((r) => setTimeout(r, 250)); }
    assert(ready, 'Server QA belum siap.');
    browser = await chromium.launch({ headless: true });
    const open = async (role) => {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        context.setDefaultTimeout(15000); context.setDefaultNavigationTimeout(20000);
        await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await context.newPage();
        target.on('pageerror', (e) => errors.push(e.message));
        target.on('console', (m) => { if (['error', 'warning'].includes(m.type()) && !expectedFailure) errors.push(m.text()); });
        target.on('response', (r) => { if (r.url().startsWith(url) && r.status() >= 400 && !expectedFailure) unexpectedNetwork.push(`${r.status()} ${new URL(r.url()).pathname}`); });
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`); await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard'); return { context, target };
    };
    ({ target: page } = await open('manager'));
    await page.goto(`${url}/sales/returns`);
    const dialog = () => page.getByRole('dialog');
    const openRefund = async (key) => { await page.getByRole('button', { name: `Refund ${setup.numbers[setup[key]]}`, exact: true }).click(); };
    const confirm = () => dialog().getByRole('button', { name: 'Konfirmasi refund', exact: true });
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        if (await dialog().isVisible()) { const d = await dialog().boundingBox(); const v = page.viewportSize(); assert(d && d.x >= 0 && d.y >= 0 && d.x + d.width <= v.width + 1 && d.y + d.height <= v.height + 1, `${name}: dialog framing`); }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: PASS`);
    };
    assert.equal(await page.getByRole('button', { name: 'Tambah draft retur', exact: true }).count(), 0);
    await page.keyboard.press('Alt+f'); assert(await page.getByLabel('Cari retur', { exact: true }).evaluate((e) => e === document.activeElement));
    expectedFailure = true;
    const quoteUrl = `**/sales/returns/${setup.first}/refund-quote`;
    for (const status of [403, 500]) {
        await page.route(quoteUrl, (r) => r.fulfill({ status, contentType: 'application/json', body: '{}' }));
        await openRefund('first'); await dialog().getByRole('alert').waitFor(); assert.equal(await confirm().count(), 0);
        await page.unroute(quoteUrl); await dialog().getByRole('button', { name: 'Muat ulang quote', exact: true }).click(); await confirm().waitFor();
        await page.keyboard.press('Escape'); await dialog().waitFor({ state: 'hidden' });
    }
    expectedFailure = false;
    checks.push('Quote 403/500, reload, dedicated refund permission and keyboard: PASS');
    // An aborted old quote must not replace a newly opened document.
    await page.route(quoteUrl, async (r) => { await new Promise((resolve) => setTimeout(resolve, 800)); await r.continue().catch(() => {}); });
    await openRefund('first'); await dialog().getByText('Memuat nominal refund...', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await dialog().waitFor({ state: 'hidden' }); await openRefund('target'); await confirm().waitFor(); await page.waitForTimeout(1100);
    assert(await dialog().locator('dd').filter({ hasText: /^Rp 30$/ }).isVisible()); await page.keyboard.press('Escape'); await dialog().waitFor({ state: 'hidden' }); await page.unroute(quoteUrl);
    await openRefund('first'); await confirm().waitFor(); assert.equal(await page.getByLabel('Jumlah 1', { exact: true }).inputValue(), '40');
    await page.getByLabel('Jumlah 1', { exact: true }).fill('40.00'); assert(await confirm().isDisabled());
    await page.getByLabel('Jumlah 1', { exact: true }).fill('40,01'); assert(await confirm().isDisabled());
    await page.getByLabel('Jumlah 1', { exact: true }).fill('15'); await page.getByRole('button', { name: 'Tambah metode refund', exact: true }).click();
    await page.getByRole('combobox', { name: 'Metode 1', exact: true }).click(); await page.getByRole('option', { name: 'Tunai', exact: true }).click();
    await page.getByRole('combobox', { name: 'Metode 2', exact: true }).click();
    assert(await page.getByRole('option', { name: 'Tunai', exact: true }).isDisabled()); await page.getByRole('option', { name: 'Transfer', exact: true }).click();
    await page.getByLabel('Jumlah 2', { exact: true }).fill('25'); assert(await confirm().isEnabled());
    await shot('positive-multi-method');
    const payUrl = `**/sales/returns/${setup.first}/refund`;
    let attempts = 0; let releaseTimeout;
    await page.clock.install();
    await page.route(payUrl, async (r) => {
        payloads.push(r.request().postDataJSON()); attempts++;
        if (attempts === 1) { await new Promise((resolve) => { releaseTimeout = resolve; }); return r.abort('timedout').catch(() => {}); }
        if (attempts === 2) return r.fulfill({ status: 500, contentType: 'application/json', body: '{}' });
        if (attempts === 3) { const committed = await r.fetch(); assert.equal(committed.status(), 200); return r.abort('failed'); }
        return r.continue();
    });
    expectedFailure = true;
    const firstRequest = page.waitForRequest((r) => r.url() === `${url}/sales/returns/${setup.first}/refund`);
    await page.keyboard.press('Control+Enter'); await firstRequest; await page.clock.runFor(30001);
    await dialog().getByText('Hasil belum terkonfirmasi. Periksa ulang dengan permintaan yang sama.', { exact: true }).waitFor();
    releaseTimeout();
    assert(await page.getByLabel('Jumlah 1', { exact: true }).isDisabled()); await page.keyboard.press('Escape'); assert(await dialog().isVisible());
    await dialog().getByRole('button', { name: 'Periksa ulang hasil', exact: true }).click(); await dialog().getByRole('button', { name: 'Periksa ulang hasil', exact: true }).waitFor();
    await dialog().getByRole('button', { name: 'Periksa ulang hasil', exact: true }).click(); await dialog().getByRole('button', { name: 'Periksa ulang hasil', exact: true }).waitFor();
    await dialog().getByRole('button', { name: 'Periksa ulang hasil', exact: true }).click(); await dialog().getByRole('heading', { name: 'Hasil refund retur', exact: true }).waitFor();
    expectedFailure = false; assert.equal(attempts, 4); for (const payload of payloads) assert.deepEqual(payloads[0], payload);
    fixture({ verify: setup.fingerprint, settlements: 1, refunds: 1 }); checks.push('30s client timeout + 5xx before commit + lost response after commit, identical retry, no duplicate payout: PASS');
    await page.unroute(payUrl); await page.keyboard.press('Escape'); await dialog().waitFor({ state: 'hidden' });
    await openRefund('target'); await confirm().waitFor(); fixture({ settle: setup.sibling });
    expectedFailure = true; await confirm().click(); await dialog().getByRole('alert').waitFor(); assert.equal(await confirm().count(), 0);
    await dialog().getByRole('button', { name: 'Muat ulang quote', exact: true }).click(); await confirm().waitFor(); expectedFailure = false;
    await confirm().click(); await dialog().getByRole('heading', { name: 'Hasil refund retur', exact: true }).waitFor(); await page.keyboard.press('Escape'); await dialog().waitFor({ state: 'hidden' });
    checks.push('Real sibling settlement invalidates stale quote and requires explicit re-confirmation: PASS');
    await openRefund('zero'); await dialog().getByText('Tanpa pengembalian uang', { exact: true }).waitFor();
    assert.equal(await dialog().getByRole('combobox').count(), 0); await shot('zero-confirmation');
    await page.keyboard.press('Control+Enter'); await dialog().getByRole('heading', { name: 'Hasil refund retur', exact: true }).waitFor();
    await page.keyboard.press('Escape'); await dialog().waitFor({ state: 'hidden' });
    await openRefund('cent'); await confirm().waitFor(); assert.equal(await page.getByLabel('Jumlah 1', { exact: true }).inputValue(), '0,01');
    await confirm().click(); await dialog().getByRole('heading', { name: 'Hasil refund retur', exact: true }).waitFor(); await page.keyboard.press('Escape'); await dialog().waitFor({ state: 'hidden' });
    const state = fixture({ verify: setup.fingerprint, settlements: 5, refunds: 4 }); console.log('QA refund: positive/zero/stale/recovery PASS.');
    await page.reload(); assert.equal(await page.getByRole('button', { name: /^Refund SR-/ }).count(), 0);
    const resultName = `Hasil refund ${setup.numbers[setup.first]}`;
    for (const mode of ['Light', 'Dark']) {
        await page.setViewportSize({ width: 1440, height: 1000 }); await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const current = await page.locator('[data-sidebar="sidebar"]').first().evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((current === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click(); await page.waitForTimeout(250);
                await shot(`${mode}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
            await page.getByRole('button', { name: resultName, exact: true }).click(); await shot(`${mode}-result-${width}`); await page.keyboard.press('Escape');
        }
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto(`${url}/finance/payments/history?search=${encodeURIComponent(setup.numbers[setup.first])}`);
    await page.locator('tbody').getByText('Refund retur', { exact: true }).waitFor();
    await page.getByRole('button', { name: `Detail ${setup.numbers[setup.first]}`, exact: true }).click();
    await dialog().getByText('Refund retur', { exact: true }).waitFor(); await dialog().getByText('Tunai', { exact: true }).waitFor();
    await dialog().getByText('Transfer', { exact: true }).waitFor(); await shot('finance-refund-detail');
    await page.keyboard.press('Escape'); await page.goto(`${url}/finance/payments/history?search=${encodeURIComponent(setup.numbers[setup.zero])}`);
    await page.getByText('Tidak ada pembayaran.', { exact: true }).waitFor();
    const viewer = await open('viewer'); await viewer.target.goto(`${url}/sales/returns`);
    assert.equal(await viewer.target.getByRole('button', { name: /^Refund SR-/ }).count(), 0);
    await viewer.target.getByRole('button', { name: resultName, exact: true }).click(); await viewer.target.getByRole('heading', { name: 'Hasil refund retur', exact: true }).waitFor();
    assert.equal(await viewer.target.getByRole('button', { name: 'Konfirmasi refund', exact: true }).count(), 0); await viewer.context.close();
    assert.deepEqual(errors, []); assert.deepEqual(unexpectedNetwork, []);
    const report = { status: 'PASS', checks: [...checks, 'Finance refund source/detail/search, zero absent, viewer readonly, clean console/network: PASS'], state, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', error: error.message, checks, errors, unexpectedNetwork }, null, 2)); throw error;
} finally {
    if (browser) await browser.close();
    if (server) { server.kill(); await new Promise((resolve) => server.once('exit', resolve)); }
    await unlink(database).catch(() => {});
}
