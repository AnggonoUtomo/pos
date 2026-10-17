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
const artifacts = path.join(root, 'storage/framework/testing', `purchase-receipt-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const socket = createServer(); socket.once('error', reject); socket.listen(0, '127.0.0.1', () => { const p = socket.address().port; socket.close(() => resolve(p)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `receipt_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = (input) => { const result = spawnSync('php', ['tests/Browser/purchase-drafts-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(result.status, 0, 'Fixture/verifikasi receipt gagal.'); };
const password = randomBytes(32).toString('hex'); fixture({ password, receipt: true });
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
let browser; let page; const errors = []; const checks = [];
try {
    for (let i = 0; i < 80; i++) { try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Wait isolated server. */ } await new Promise((r) => setTimeout(r, 250)); }
    browser = await chromium.launch({ headless: true });
    const open = async (role) => {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await context.newPage(); target.on('pageerror', (e) => errors.push(e.message)); target.on('console', (m) => { if (['error', 'warning'].includes(m.type())) errors.push(m.text()); });
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`); await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard');
        await target.goto(`${url}/purchasing/purchase-orders`); await target.getByRole('heading', { name: 'Order pembelian', exact: true }).waitFor(); return { context, target };
    };
    const settle = async (target = page) => { await target.getByRole('dialog').waitFor({ state: 'detached' }); await target.locator('section[aria-busy="false"]').waitFor(); };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: page overflow`);
        const dialog = page.getByRole('dialog');
        if (await dialog.isVisible()) {
            const b = await dialog.boundingBox(); const v = page.viewportSize(); assert(b && b.x >= 0 && b.y >= 0 && b.x + b.width <= v.width + 1 && b.y + b.height <= v.height + 1, `${name}: dialog frame`);
            for (const locator of [dialog.getByLabel('Total pembelian'), dialog.getByRole('button', { name: /^(Tutup|Batal)$/ })]) {
                if (await locator.count()) { const bounds = await locator.boundingBox(); assert(bounds && bounds.x >= b.x && bounds.x + bounds.width <= b.x + b.width, `${name}: content/footer overflow`); }
            }
        }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: framing PASS`);
    };
    ({ target: page } = await open('manager'));
    const button = page.getByRole('button', { name: /^Terima PO-/ }).first();
    const number = (await button.getAttribute('aria-label')).slice('Terima '.length);
    await button.click();
    const edit = await page.context().newPage(); await edit.goto(`${url}/purchasing/purchase-orders`);
    await edit.getByRole('button', { name: `Edit ${number}`, exact: true }).click(); await edit.getByLabel('Catatan').fill('QA receipt revision');
    await edit.getByRole('button', { name: 'Simpan draft', exact: true }).click(); await settle(edit); await edit.close();
    await page.getByRole('dialog').getByRole('button', { name: 'Terima barang', exact: true }).click();
    await page.getByText('Draft sudah berubah. Muat ulang halaman sebelum menerima barang.', { exact: true }).waitFor(); await shot('stale-post-error');
    await page.getByRole('button', { name: 'Batal', exact: true }).click(); await settle(); await page.reload();
    await page.getByRole('button', { name: `Terima ${number}`, exact: true }).click();
    for (const [width, height] of [[1024, 768], [768, 1024]]) { await page.setViewportSize({ width, height }); await shot(`confirm-${width}`); }
    const receiver = await open('receiver');
    assert.equal(await receiver.target.getByRole('button', { name: /^(Tambah draft|Edit |Hapus )/ }).count(), 0);
    await receiver.target.getByRole('button', { name: `Terima ${number}`, exact: true }).click();
    await receiver.target.getByRole('button', { name: 'Terima barang', exact: true }).click(); await settle(receiver.target);
    await receiver.target.getByRole('row').filter({ hasText: number }).getByText('Diterima', { exact: true }).waitFor();
    await page.keyboard.press('Control+Enter'); await settle(); await receiver.context.close();
    assert.equal(await page.getByRole('button', { name: /^(Terima PO-|Edit PO-|Hapus PO-)/ }).count(), 0);
    await page.reload(); await page.getByRole('combobox', { name: 'Filter status pembelian' }).click(); await page.getByRole('option', { name: 'Draft', exact: true }).click();
    await page.getByText('Tidak ada pembelian.', { exact: true }).waitFor();
    await page.getByRole('combobox', { name: 'Filter status pembelian' }).click(); await page.getByRole('option', { name: 'Diterima', exact: true }).click();
    await page.getByRole('button', { name: `Detail ${number}`, exact: true }).waitFor();
    for (const mode of ['Light', 'Dark']) {
        await page.setViewportSize({ width: 1440, height: 1000 }); await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const state = await page.locator('[data-sidebar="sidebar"]').first().evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((state === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click(); await page.waitForTimeout(250);
                await shot(`${mode.toLowerCase()}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
        }
        await page.getByRole('button', { name: `Detail ${number}`, exact: true }).click();
        await page.getByRole('heading', { name: 'Detail penerimaan pembelian', exact: true }).waitFor();
        assert.equal(await page.getByRole('button', { name: 'Simpan draft', exact: true }).count(), 0);
        await shot(`${mode.toLowerCase()}-posted-detail`); await page.getByRole('button', { name: 'Tutup', exact: true }).click(); await settle();
    }
    await page.goto(`${url}/inventory/stock?tab=balances`); await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 2);
    assert((await page.locator('tbody').innerText()).includes('Kamera IP 2MP')); await shot('purchase-only-balances');
    await page.goto(`${url}/inventory/stock?tab=movements`); await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 2);
    assert((await page.locator('tbody').innerText()).includes(number)); await shot('purchase-stock-card');
    const viewer = await open('viewer'); assert.equal(await viewer.target.getByRole('button', { name: /^(Terima PO-|Edit PO-|Hapus PO-)/ }).count(), 0);
    await viewer.target.getByRole('button', { name: `Detail ${number}`, exact: true }).click(); await viewer.target.getByRole('heading', { name: 'Detail penerimaan pembelian', exact: true }).waitFor(); await viewer.context.close();
    fixture({ verify_receipt: true }); assert.deepEqual(errors, [], 'Console/page errors');
    checks.push('stale reject + original-version replay, receiver view/post without manage, posted readonly, filters, snapshot, balances/card without opening and no Finance PASS');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) }; await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
