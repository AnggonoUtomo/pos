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
const artifacts = path.join(root, 'storage/framework/testing', `sales-returns-browser-${id}`);
const database = `${artifacts}.sqlite`; await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const s = createServer(); s.once('error', reject); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `returns_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = (input) => { const r = spawnSync('php', ['tests/Browser/sales-returns-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(r.status, 0, 'Fixture/verifikasi retur gagal.'); return JSON.parse(r.stdout); };
const password = randomBytes(32).toString('hex'); const setup = fixture({ password });
console.log('QA: fixture retur siap.');
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const errors = []; const expected = []; const checks = []; let browser; let page; let expectedFailure = false;
try {
    let ready = false;
    for (let i = 0; i < 20; i++) { try { const response = await fetch(`${url}/login`, { signal: AbortSignal.timeout(2500) }); await response.arrayBuffer(); if (response.ok) { ready = true; break; } } catch { /* Await server. */ } await new Promise((r) => setTimeout(r, 250)); }
    assert(ready, 'Server QA belum siap.');
    console.log('QA: server retur siap.');
    browser = await chromium.launch({ headless: true });
    console.log('QA: Chrome QA siap.');
    const open = async (role) => {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } }); context.setDefaultTimeout(15000); context.setDefaultNavigationTimeout(15000);
        await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await context.newPage(); target.on('pageerror', (e) => errors.push(e.message)); target.on('console', (m) => { if (['error', 'warning'].includes(m.type())) (expectedFailure ? expected : errors).push(m.text()); });
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`); await target.getByLabel('Password', { exact: true }).fill(password); await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard'); return { context, target };
    };
    ({ target: page } = await open('manager'));
    await page.getByRole('link', { name: /Retur Penjualan/ }).click(); await page.getByRole('heading', { name: 'Retur penjualan', exact: true }).waitFor();
    await page.getByText('Tidak ada draft retur.', { exact: true }).waitFor(); console.log('QA: halaman dan empty state siap.');
    const dialog = () => page.getByRole('dialog');
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        if (await dialog().isVisible()) { const d = await dialog().boundingBox(); const v = page.viewportSize(); assert(d && d.x >= 0 && d.y >= 0 && d.x + d.width <= v.width + 1 && d.y + d.height <= v.height + 1, `${name}: dialog framing`); }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: PASS`);
    };
    await page.keyboard.press('Alt+f'); assert(await page.getByLabel('Cari retur', { exact: true }).evaluate((e) => e === document.activeElement));
    await page.keyboard.press('Control+Shift+n'); await dialog().getByRole('heading', { name: 'Tambah draft retur', exact: true }).waitFor();
    await page.getByRole('combobox', { name: 'Invoice asal', exact: true }).click(); await page.getByRole('option', { name: new RegExp(setup.number) }).click(); await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).waitFor();
    await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).fill('3'); await page.keyboard.press('Control+Enter'); await dialog().getByText('Kuantitas harus positif dan tidak melebihi jumlah terjual.', { exact: true }).waitFor();
    await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).fill('1,5'); await page.keyboard.press('Control+Enter'); await dialog().waitFor({ state: 'hidden' });
    let number = (await page.locator('tbody tr').first().innerText()).match(/SR-[A-Z0-9]{26}/)[0];
    await page.getByRole('button', { name: `Edit ${number}`, exact: true }).click(); await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).waitFor(); assert.equal(await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).inputValue(), '1,5');
    await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).fill('1'); await page.getByLabel('Catatan', { exact: true }).fill('QA pemeriksaan kamera'); await page.keyboard.press('Control+Enter'); await dialog().waitFor({ state: 'hidden' });
    await page.getByRole('button', { name: `Hapus ${number}`, exact: true }).click(); await page.keyboard.press('Control+Enter'); await dialog().waitFor({ state: 'hidden' }); await page.getByText('Tidak ada draft retur.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click(); await page.getByRole('button', { name: `Pulihkan ${number}`, exact: true }).click(); await page.keyboard.press('Control+Enter'); await dialog().waitFor({ state: 'hidden' });
    await page.getByRole('button', { name: 'Data', exact: true }).click(); await page.getByRole('button', { name: `Detail ${number}`, exact: true }).waitFor(); console.log('QA: tambah/edit/hapus/pulihkan dan angka Indonesia PASS.');
    await page.getByLabel('Cari retur', { exact: true }).fill('%'); await page.getByRole('button', { name: 'Cari', exact: true }).click(); await page.getByText('Tidak ada draft retur.', { exact: true }).waitFor();
    await page.getByLabel('Cari retur', { exact: true }).fill(''); await page.getByRole('button', { name: 'Cari', exact: true }).click(); await page.getByRole('button', { name: `Detail ${number}`, exact: true }).waitFor();
    expectedFailure = true; let fail = true;
    const sourceUrl = `**/sales/returns/invoices/${setup.invoice_id}`;
    await page.route(sourceUrl, (r) => fail ? (fail = false, r.fulfill({ status: 500, contentType: 'application/json', body: '{}' })) : r.continue());
    await page.getByRole('button', { name: `Edit ${number}`, exact: true }).click(); await page.getByRole('alert').filter({ hasText: 'Snapshot belum dapat dimuat.' }).waitFor();
    assert(await dialog().getByRole('button', { name: 'Simpan draft', exact: true }).isDisabled()); await page.getByRole('button', { name: 'Muat ulang snapshot', exact: true }).click(); await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).waitFor(); await page.keyboard.press('Escape'); await page.unroute(sourceUrl); expectedFailure = false;
    for (const mode of ['Light', 'Dark']) {
        await page.setViewportSize({ width: 1440, height: 1000 }); await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const state = await page.locator('[data-sidebar="sidebar"]').first().evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((state === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click(); await page.waitForTimeout(250);
                await shot(`${mode.toLowerCase()}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
            await page.getByRole('button', { name: `Edit ${number}`, exact: true }).click(); await page.getByLabel(`Kuantitas retur ${setup.sku}`, { exact: true }).waitFor(); await shot(`${mode.toLowerCase()}-form-${width}`); await page.keyboard.press('Escape');
        }
    }
    await page.keyboard.press('Control+k'); await dialog().getByPlaceholder('Cari menu atau operasi...').fill('Retur'); assert(await dialog().getByText('Retur Penjualan', { exact: true }).isVisible()); await page.keyboard.press('Escape');
    const viewer = await open('viewer'); await viewer.target.goto(`${url}/sales/returns`); assert.equal(await viewer.target.getByRole('button', { name: 'Tambah draft retur', exact: true }).count(), 0); await viewer.target.getByRole('button', { name: `Detail ${number}`, exact: true }).click(); assert.equal(await viewer.target.getByRole('button', { name: 'Simpan draft', exact: true }).count(), 0); await viewer.context.close();
    expectedFailure = true; const outsider = await open('outsider'); const denied = await outsider.target.goto(`${url}/sales/returns`); assert.equal(denied.status(), 403); await outsider.context.close(); expectedFailure = false;
    const state = fixture({ verify: setup.fingerprint }); assert.deepEqual(state.events, ['created', 'updated', 'deleted', 'restored']); assert.deepEqual(errors, []);
    const report = { status: 'PASS', checks: [...checks, 'CRUD, validation, source retry, permissions, shortcut/palette, unchanged invoice/stock/finance PASS'], console: errors, expectedFailures: expected.length, state, artifacts: path.relative(root, artifacts) }; await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', error: error.message, checks, console: errors }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
