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
const artifacts = path.join(root, 'storage/framework/testing', `purchase-drafts-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const value = socket.address().port; socket.close(() => resolve(value)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `purchase_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = (input) => {
    const result = spawnSync('php', ['tests/Browser/purchase-drafts-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, 'Fixture/persistence check gagal; periksa PHP helper.');
};
const password = randomBytes(32).toString('hex'); fixture({ password });
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
let browser; let page;
const checks = []; const errors = [];
try {
    for (let i = 0; i < 80; i++) {
        try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Wait isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const open = async () => {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await context.newPage(); target.on('pageerror', (e) => errors.push(e.message));
        target.on('console', (m) => { if (['error', 'warning'].includes(m.type())) errors.push(m.text()); });
        return { context, target };
    };
    const login = async (target, role) => {
        await target.goto(`${url}/login`); await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click(); await target.waitForURL('**/dashboard');
    };
    const settle = async () => { await page.getByRole('dialog').waitFor({ state: 'detached' }); await page.locator('section[aria-busy="false"]').waitFor(); };
    const choose = async (label, name) => { await page.getByLabel(label, { exact: true }).click(); await page.getByRole('option', { name, exact: true }).click(); };
    const shot = async (label) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${label}: page overflow`);
        const dialog = page.getByRole('dialog');
        if (await dialog.isVisible()) {
            const b = await dialog.boundingBox(); const v = page.viewportSize();
            assert(b && b.x >= 0 && b.y >= 0 && b.x + b.width <= v.width + 1 && b.y + b.height <= v.height + 1, `${label}: dialog framing`);
            const total = dialog.getByLabel('Total pembelian');
            if (await total.count()) {
                const bounds = await total.boundingBox();
                assert(bounds && bounds.x >= b.x && bounds.x + bounds.width <= b.x + b.width, `${label}: total terpotong`);
                const close = dialog.getByRole('button', { name: /^(Tutup|Batal)$/ });
                const closeBounds = await close.boundingBox();
                assert(closeBounds && closeBounds.x + closeBounds.width <= b.x + b.width, `${label}: footer terpotong`);
            }
        }
        await page.screenshot({ path: path.join(artifacts, `${label}.png`), fullPage: true, animations: 'disabled' }); checks.push(`${label}: framing PASS`);
    };
    ({ target: page } = await open()); await login(page, 'manager');
    await page.goto(`${url}/purchasing/purchase-orders`);
    await page.getByRole('heading', { name: 'Order pembelian', exact: true }).waitFor();
    assert.equal(await page.locator('section').getByRole('button', { name: /posting|terima barang|bayar/i }).count(), 0);
    await page.keyboard.press('Control+Shift+N');
    await choose('Supplier', 'DEMO-SUP-CCTV - Distribusi CCTV Demo'); await choose('Gudang', 'WH-MAIN - Gudang Utama');
    await choose('Barang 1', 'DEMO-ELEC-NYM-3X15 - Kabel NYM 3x1.5 mm2'); await choose('Satuan 1', 'Rol');
    await page.getByLabel('Kuantitas 1', { exact: true }).fill('0,125'); await page.getByLabel('Harga beli 1', { exact: true }).fill('900.000');
    await page.getByLabel('Catatan', { exact: true }).fill('QA purchase');
    assert((await page.getByLabel('Total pembelian').innerText()).includes('112.500'));
    for (const [width, height] of [[1024, 768], [768, 1024]]) { await page.setViewportSize({ width, height }); await shot(`form-${width}`); }
    await page.getByLabel('Harga beli 1', { exact: true }).fill('0');
    await page.getByRole('button', { name: 'Simpan draft', exact: true }).click();
    await page.getByText('Kuantitas dan harga beli wajib lebih dari nol.', { exact: true }).waitFor();
    assert.equal(await page.getByLabel('Kuantitas 1', { exact: true }).inputValue(), '0,125');
    await page.getByLabel('Harga beli 1', { exact: true }).fill('900.000');
    await page.keyboard.press('Control+Enter'); await settle(); await page.reload();
    const row = page.getByRole('row').filter({ hasText: '112.500' }); await row.waitFor();
    const detailName = await row.getByRole('button', { name: /^Detail / }).getAttribute('aria-label');
    const number = detailName.slice('Detail '.length);
    await row.getByRole('button', { name: detailName, exact: true }).click();
    assert((await page.getByRole('dialog').innerText()).includes('12,5'));
    await page.getByRole('button', { name: 'Tutup', exact: true }).click(); await settle();
    await page.getByRole('button', { name: `Edit ${number}`, exact: true }).click();
    assert.equal(await page.getByLabel('Kuantitas 1', { exact: true }).inputValue(), '0,125');
    const stale = await page.context().newPage(); await stale.goto(`${url}/purchasing/purchase-orders`);
    await stale.getByRole('button', { name: `Edit ${number}`, exact: true }).click();
    await page.getByLabel('Kuantitas 1', { exact: true }).fill('0,25');
    await page.getByLabel('Catatan', { exact: true }).fill('QA purchase updated');
    await page.keyboard.press('Control+Enter'); await settle();
    await stale.getByLabel('Catatan', { exact: true }).fill('STALE overwrite');
    await stale.getByRole('button', { name: 'Simpan draft', exact: true }).click();
    await stale.getByText('Draft sudah berubah. Muat ulang halaman sebelum melanjutkan.', { exact: true }).waitFor(); await stale.close();
    for (const operation of ['Hapus', 'Pulihkan']) {
        await page.getByRole('button', { name: `${operation} ${number}`, exact: true }).click();
        await page.keyboard.press('Control+Enter'); await settle();
        await page.getByRole('button', { name: operation === 'Hapus' ? 'Terhapus' : 'Data', exact: true }).click();
        await page.getByRole('button', { name: `${operation === 'Hapus' ? 'Pulihkan' : 'Edit'} ${number}`, exact: true }).waitFor();
    }
    await choose('Filter supplier', 'DEMO-SUP-ELEC - Distributor Kelistrikan Demo');
    await page.getByText('Tidak ada pembelian.', { exact: true }).waitFor(); await shot('empty');
    await choose('Filter supplier', 'Semua supplier');
    await page.keyboard.press('Alt+f'); await page.getByLabel('Cari pembelian').fill(number);
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1);
    await page.goto(`${url}/purchasing/purchase-orders`);
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click(); await page.getByText('Shortcut Halaman', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await page.keyboard.press('Control+k'); await page.getByRole('dialog').getByRole('button', { name: /^Order Pembelian/ }).click(); await settle();
    for (const mode of ['Light', 'Dark']) {
        await page.setViewportSize({ width: 1440, height: 1000 }); await page.getByRole('button', { name: 'Tema', exact: true }).click();
        await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const state = await page.locator('[data-sidebar="sidebar"]').first().evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((state === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click();
                await page.waitForTimeout(250); await shot(`${mode.toLowerCase()}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
        }
        await page.getByRole('button', { name: `Detail ${number}`, exact: true }).click(); await shot(`${mode.toLowerCase()}-detail-tablet`);
        await page.getByRole('button', { name: 'Tutup', exact: true }).click(); await settle();
    }
    for (const role of ['viewer', 'outsider']) {
        const { context, target } = await open(); await login(target, role);
        if (role === 'viewer') {
            await target.goto(`${url}/purchasing/purchase-orders`); await target.getByRole('heading', { name: 'Order pembelian', exact: true }).waitFor();
            assert.equal(await target.getByRole('button', { name: /^(Tambah draft|Edit |Hapus |Pulihkan )/ }).count(), 0);
            await target.getByRole('button', { name: `Detail ${number}`, exact: true }).click();
            assert.equal(await target.getByRole('button', { name: 'Simpan draft', exact: true }).count(), 0);
            await target.getByRole('button', { name: 'Tutup', exact: true }).click();
            await target.getByRole('dialog').waitFor({ state: 'detached' });
        } else {
            assert.equal(await target.getByRole('link', { name: /^Order Pembelian/ }).count(), 0);
            assert.equal((await context.request.get(`${url}/purchasing/purchase-orders/data`, { headers: { Accept: 'application/json' } })).status(), 403);
        }
        await target.keyboard.press('Control+k');
        assert.equal(await target.getByRole('dialog').getByRole('button', { name: /^Order Pembelian/ }).count(), role === 'viewer' ? 1 : 0);
        await context.close(); checks.push(`${role}: permissions PASS`);
    }
    fixture({ verify: true }); assert.deepEqual(errors, [], 'Console/page errors');
    checks.push('CRUD/reload/exact Indonesia/error preservation/stale two tabs/filter/shortcuts/permissions/audit/no-ledger PASS');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
