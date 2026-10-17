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
const artifacts = path.join(root, 'storage/framework/testing', `payment-history-browser-${id}`);
const database = `${artifacts}.sqlite`; await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const s = createServer(); s.once('error', reject); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `history_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', APP_TIMEZONE: 'UTC' };
const fixture = (input) => { const r = spawnSync('php', ['tests/Browser/payment-history-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(r.status, 0, 'Fixture/verifikasi history gagal.'); return r.stdout; };
const password = randomBytes(32).toString('hex'); const setup = JSON.parse(fixture({ password }));
console.log('QA: fixture riwayat siap.');
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const checks = []; const errors = []; const expected = []; let browser; let page; let expectedFailure = false;
try {
    for (let i = 0; i < 80; i++) { try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Wait isolated server. */ } await new Promise((r) => setTimeout(r, 250)); }
    browser = await chromium.launch({ headless: true });
    const open = async (role) => {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        context.setDefaultTimeout(15000); context.setDefaultNavigationTimeout(15000);
        await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await context.newPage(); target.on('pageerror', (e) => errors.push(e.message)); target.on('console', (m) => { if (['error', 'warning'].includes(m.type())) (expectedFailure ? expected : errors).push(m.text()); });
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`); await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard'); return { context, target };
    };
    ({ target: page } = await open('viewer')); console.log('QA: login viewer selesai.'); await page.getByRole('link', { name: /Pembayaran/, exact: true }).click(); await page.getByRole('heading', { name: 'Riwayat pembayaran', exact: true }).waitFor(); console.log('QA: halaman riwayat siap.');
    const settle = async () => { await page.locator('section[aria-busy="false"]').waitFor(); };
    const apply = async () => { await page.getByRole('button', { name: 'Terapkan', exact: true }).click(); await settle(); };
    const reset = async () => { await page.getByRole('button', { name: 'Reset filter', exact: true }).click(); await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 3); await settle(); };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: page overflow`);
        const dialog = page.getByRole('dialog'); if (await dialog.isVisible()) { const d = await dialog.boundingBox(); const v = page.viewportSize(); assert(d && d.x >= 0 && d.y >= 0 && d.x + d.width <= v.width + 1 && d.y + d.height <= v.height + 1, `${name}: dialog frame`); const close = dialog.getByRole('button', { name: 'Tutup', exact: true }); if (await close.count()) { const footer = await close.boundingBox(); assert(footer && footer.x >= d.x && footer.x + footer.width <= d.x + d.width + 1, `${name}: footer frame`); } }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: framing PASS`);
    };
    assert.equal(await page.locator('tbody tr').count(), 3);
    await page.keyboard.press('Alt+f'); assert(await page.getByLabel('Nomor transaksi', { exact: true }).evaluate((e) => e === document.activeElement));
    await page.getByLabel('Nomor transaksi', { exact: true }).fill('%_'); await page.keyboard.press('Control+Enter'); await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1); await settle();
    assert((await page.locator('tbody').innerText()).includes('POS-QA!%_A')); await reset();
    await page.getByLabel('Dari tanggal', { exact: true }).fill('2026-10-16'); await page.getByLabel('Sampai tanggal', { exact: true }).fill('2026-10-16'); await apply(); await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 2); await page.reload(); assert.equal(await page.locator('tbody tr').count(), 2); await reset();
    await page.getByRole('combobox', { name: 'Akun', exact: true }).click(); await page.getByRole('option', { name: 'Bank Operasional Demo', exact: true }).click();
    await page.getByRole('combobox', { name: 'Metode', exact: true }).click(); await page.getByRole('option', { name: 'Tunai', exact: true }).click(); await apply(); await page.getByText('Tidak ada pembayaran.', { exact: true }).waitFor(); await shot('empty-cross-split'); await reset();
    await page.getByRole('button', { name: 'Detail POS-QA!%_A', exact: true }).click(); await page.getByRole('dialog').getByText('Rp 1.000', { exact: true }).waitFor(); assert((await page.getByRole('dialog').innerText()).includes('Rp 4.000,12')); await page.keyboard.press('Escape');
    // Controlled 500 must show retry, not an unhandled exception or stale content.
    let fail = true; expectedFailure = true;
    const outgoing = `**/finance/payments/history/outgoing/${setup.fixture.outgoing}`;
    await page.route(outgoing, async (r) => { if (fail) { fail = false; await r.fulfill({ status: 500, contentType: 'application/json', body: '{}' }); } else await r.continue(); });
    await page.getByRole('button', { name: 'Detail PO-CCTV-QA', exact: true }).click(); await page.getByRole('alert').getByText('Detail pembayaran belum dapat dimuat.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Muat ulang detail', exact: true }).click(); await page.getByRole('dialog').getByText('Rp 123,123456', { exact: true }).first().waitFor(); expectedFailure = false; await page.unroute(outgoing); await shot('detail-retry'); await page.keyboard.press('Escape');
    const incoming = `**/finance/payments/history/incoming/${setup.fixture.incoming}`;
    await page.route(incoming, async (r) => { await new Promise((resolve) => setTimeout(resolve, 800)); await r.continue().catch(() => {}); });
    await page.getByRole('button', { name: 'Detail POS-QA!%_A', exact: true }).click(); await page.getByText('Memuat detail...', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Detail PO-CCTV-QA', exact: true }).click(); await page.getByRole('dialog').getByText('Rp 123,123456', { exact: true }).first().waitFor(); await page.waitForTimeout(1000); assert((await page.getByRole('dialog').innerText()).includes('PO-CCTV-QA')); await page.keyboard.press('Escape'); await page.unroute(incoming);
    for (const mode of ['Light', 'Dark']) {
        await page.setViewportSize({ width: 1440, height: 1000 }); await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const state = await page.locator('[data-sidebar="sidebar"]').first().evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((state === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click(); await page.waitForTimeout(250);
                await shot(`${mode.toLowerCase()}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
            await page.getByRole('button', { name: 'Detail PO-CCTV-QA', exact: true }).click(); await page.getByRole('dialog').getByText('Rp 123,123456', { exact: true }).first().waitFor(); await shot(`${mode.toLowerCase()}-detail-${width}`); await page.keyboard.press('Escape');
        }
    }
    await page.keyboard.press('Control+k'); await page.getByRole('dialog').getByPlaceholder('Cari menu atau operasi...').fill('Pembayaran'); await shot('palette'); await page.keyboard.press('Escape');
    for (const role of ['manager', 'outsider']) { const other = await open(role); expectedFailure = true; const response = await other.target.goto(`${url}/finance/payments/history`); assert.equal(response.status(), 403); await other.context.close(); expectedFailure = false; }
    fixture({ verify: setup.fingerprint }); assert.deepEqual(errors, [], 'Unexpected console/page errors');
    const report = { status: 'PASS', checks: [...checks, 'literal/date/same-split filters, permissions, readonly, retry/abort, shortcuts/palette, ledger/audit fingerprint unchanged PASS'], console: errors, expectedDetailFailure: expected.length, artifacts: path.relative(root, artifacts) }; await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
