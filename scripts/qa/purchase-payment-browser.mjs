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
const artifacts = path.join(root, 'storage/framework/testing', `purchase-payment-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const socket = createServer(); socket.once('error', reject); socket.listen(0, '127.0.0.1', () => { const p = socket.address().port; socket.close(() => resolve(p)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `payment_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = (input) => { const result = spawnSync('php', ['tests/Browser/purchase-drafts-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(result.status, 0, 'Fixture/verifikasi payment gagal.'); return result.stdout; };
const password = randomBytes(32).toString('hex'); const setup = JSON.parse(fixture({ password, payment: true }));
const display = (value) => { const [whole, fraction = ''] = value.split('.'); const tail = fraction.replace(/0+$/, ''); return new Intl.NumberFormat('id-ID').format(BigInt(whole)) + (tail ? `,${tail}` : ''); };
const restFixed = BigInt(setup.total.replace('.', '')) - 100000000000n;
const rest = display(`${restFixed / 1000000n}.${(restFixed % 1000000n).toString().padStart(6, '0')}`);
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
            for (const element of await dialog.locator('input,button[aria-label="Tambah metode pembayaran"],output').all()) {
                const bounds = await element.boundingBox(); if (bounds) assert(bounds.x >= b.x && bounds.x + bounds.width <= b.x + b.width + 1, `${name}: content overflow`);
            }
        }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${name}: framing PASS`);
    };
    ({ target: page } = await open('manager'));
    const number = setup.number;
    await page.getByRole('button', { name: `Bayar ${number}`, exact: true }).click();
    await page.getByLabel('Metode 1', { exact: true }).click(); await page.getByRole('option', { name: 'Tunai', exact: true }).click();
    await page.getByLabel('Jumlah 1', { exact: true }).fill('1');
    await page.getByRole('button', { name: 'Catat pembayaran', exact: true }).click();
    await page.getByText('Jumlah pembayaran harus tepat sama dengan total pembelian, tanpa kembalian.', { exact: true }).waitFor();
    assert.equal(await page.getByLabel('Jumlah 1', { exact: true }).inputValue(), '1'); await shot('underpayment-error');
    await page.getByLabel('Jumlah 1', { exact: true }).fill('100.000');
    await page.getByRole('button', { name: 'Tambah metode pembayaran', exact: true }).click();
    await page.getByLabel('Metode 2', { exact: true }).click(); await page.getByRole('option', { name: 'Transfer', exact: true }).click();
    await page.getByLabel('Jumlah 2', { exact: true }).fill(rest);
    assert.equal(await page.getByLabel('Total pembayaran', { exact: true }).innerText(), `Rp ${display(setup.total)}`);
    for (const mode of ['Light', 'Dark']) {
        await page.getByRole('button', { name: 'Batal', exact: true }).click(); await settle();
        await page.setViewportSize({ width: 1440, height: 1000 }); await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const state = await page.locator('[data-sidebar="sidebar"]').first().evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((state === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click(); await page.waitForTimeout(250);
                await shot(`${mode.toLowerCase()}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
            await page.getByRole('button', { name: `Bayar ${number}`, exact: true }).click(); await shot(`${mode.toLowerCase()}-payment-${width}`);
            await page.getByRole('button', { name: 'Batal', exact: true }).click(); await settle();
        }
        await page.getByRole('button', { name: `Bayar ${number}`, exact: true }).click();
    }
    // Two independent operators hold the same original revision and identical split.
    const fill = async (target) => {
        await target.getByLabel('Metode 1', { exact: true }).click(); await target.getByRole('option', { name: 'Tunai', exact: true }).click();
        await target.getByLabel('Jumlah 1', { exact: true }).fill('100.000'); await target.getByRole('button', { name: 'Tambah metode pembayaran', exact: true }).click();
        await target.getByLabel('Metode 2', { exact: true }).click(); await target.getByRole('option', { name: 'Transfer', exact: true }).click(); await target.getByLabel('Jumlah 2', { exact: true }).fill(rest);
    };
    await fill(page);
    const payer = await open('payer'); assert.equal(await payer.target.getByRole('button', { name: /^(Tambah draft|Terima PO-|Edit PO-)/ }).count(), 0);
    await payer.target.getByRole('button', { name: `Bayar ${number}`, exact: true }).click(); await fill(payer.target);
    await payer.target.keyboard.press('Control+Enter'); await settle(payer.target);
    await payer.target.getByRole('row').filter({ hasText: number }).getByText('Lunas', { exact: true }).waitFor(); await payer.context.close();
    await page.getByRole('button', { name: 'Catat pembayaran', exact: true }).click(); await settle(); await page.reload();
    assert.equal(await page.getByRole('button', { name: /^Bayar PO-/ }).count(), 0);
    await page.getByRole('combobox', { name: 'Filter pembayaran', exact: true }).click(); await page.getByRole('option', { name: 'Belum dicatat', exact: true }).click(); await page.getByText('Tidak ada pembelian.', { exact: true }).waitFor();
    await page.getByRole('combobox', { name: 'Filter pembayaran', exact: true }).click(); await page.getByRole('option', { name: 'Lunas', exact: true }).click();
    await page.getByRole('button', { name: `Detail ${number}`, exact: true }).click(); await page.getByRole('region', { name: 'Pembayaran tercatat', exact: true }).waitFor();
    assert((await page.getByRole('region', { name: 'Pembayaran tercatat', exact: true }).innerText()).includes('100.000')); await shot('paid-readonly-detail');
    await page.getByRole('button', { name: 'Tutup', exact: true }).click(); await settle();
    for (const role of ['viewer', 'receiver']) { const user = await open(role); assert.equal(await user.target.getByRole('button', { name: /^Bayar PO-/ }).count(), 0); await user.context.close(); }
    fixture({ verify_payment: true, stock_hash: setup.stock_hash }); assert.deepEqual(errors, [], 'Console/page errors');
    checks.push('exact Indonesia/error preservation/multi-method/original-version replay/payer permission/filter/readonly/audit/stock unchanged PASS');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) }; await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
