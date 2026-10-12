import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing, jangan pasang dependency.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `stock-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `stock_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/stock-fixture.php'], { cwd: root, env, input: JSON.stringify({ password }), encoding: 'utf8' });
assert.equal(fixture.status, 0, fixture.stderr);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true,
});
let browser;
let page;
const checks = [];
const errors = [];
try {
    for (let i = 0; i < 80; i++) {
        try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Wait for the isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, timezoneId: 'Asia/Jakarta' });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    const monitor = (target) => {
        target.on('pageerror', (error) => errors.push(error.message));
        target.on('console', (message) => { if (['error', 'warning'].includes(message.type())) errors.push(message.text()); });
    };
    monitor(page);
    const login = async (target, role) => {
        await target.goto(`${url}/login`);
        await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click();
        await target.waitForURL('**/dashboard');
        await target.goto(`${url}/inventory/stock`);
        await target.getByRole('heading', { name: 'Stok', exact: true }).waitFor();
    };
    const settle = async () => {
        await page.locator('section[aria-busy="false"]').waitFor();
        await page.getByRole('dialog').waitFor({ state: 'hidden' });
    };
    const screenshot = async (label) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${label}: overflow halaman`);
        const dialog = page.getByRole('dialog');
        if (await dialog.isVisible()) {
            const bounds = await dialog.boundingBox();
            const size = page.viewportSize();
            assert(bounds && bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= size.width + 1 && bounds.y + bounds.height <= size.height + 1, `${label}: dialog melewati viewport`);
        }
        await page.screenshot({ path: path.join(artifacts, `${label}.png`), fullPage: true, animations: 'disabled' });
        checks.push(`${label}: framing PASS`);
    };
    const choose = async (label, text) => {
        await page.getByLabel(label, { exact: true }).click();
        await page.getByRole('option', { name: text, exact: true }).click();
        await page.getByLabel(label, { exact: true }).filter({ hasText: text }).waitFor();
        if (label.startsWith('Filter ')) await settle();
    };
    const view = async (label) => {
        await page.getByRole('button', { name: label, exact: true }).click();
        await page.locator('button[aria-pressed="true"]').filter({ hasText: label }).waitFor();
        await settle();
    };
    const fillDraft = async (warehouse = 'WH-MAIN - Gudang Utama') => {
        await choose('Gudang', warehouse);
        await choose('Barang 1', 'DEMO-CCTV-IP-2MP - Kamera IP 2MP');
        await choose('Satuan 1', 'Dus');
        await page.getByLabel('Kuantitas 1', { exact: true }).fill('2');
        await page.getByLabel('Harga pokok 1', { exact: true }).fill('2000000');
    };
    await login(page, 'manager');
    await screenshot('stock-empty-desktop');
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click();
    await page.getByText('Shortcut Halaman', { exact: true }).waitFor();
    await page.keyboard.press('Escape');
    await page.keyboard.press('Control+Shift+N');
    await page.getByRole('dialog').waitFor();
    await fillDraft();
    await page.getByLabel('Harga pokok 1', { exact: true }).fill('0');
    await page.getByRole('button', { name: 'Simpan draft', exact: true }).click();
    await page.getByText('Kuantitas, rasio dan harga pokok wajib lebih dari nol.', { exact: true }).waitFor();
    await page.getByLabel('Harga pokok 1', { exact: true }).fill('2000000');
    await page.setViewportSize({ width: 1024, height: 768 }); await screenshot('stock-form-tablet-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await screenshot('stock-form-tablet-portrait');
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.keyboard.press('Control+Enter'); await settle();
    let number = (await page.locator('tbody tr').first().locator('td').first().innerText()).trim();
    await page.getByRole('button', { name: `Edit ${number}`, exact: true }).click();
    await page.getByLabel('Catatan', { exact: true }).fill('QA stok awal');
    await page.getByRole('button', { name: 'Simpan draft', exact: true }).click(); await settle();
    await page.getByRole('button', { name: `Hapus ${number}`, exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click(); await settle();
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: `Pulihkan ${number}`, exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Pulihkan', exact: true }).click(); await settle();
    await page.getByRole('button', { name: 'Data', exact: true }).click();
    await page.getByRole('button', { name: `Detail ${number}`, exact: true }).click();
    await page.getByRole('dialog').getByText('8 Unit', { exact: true }).waitFor();
    await page.getByRole('dialog').getByText('Rp 4.000.000', { exact: true }).waitFor();
    await screenshot('stock-draft-detail-desktop');
    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: `Posting ${number}`, exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Posting', exact: true }).click(); await settle();
    assert.equal(await page.getByRole('button', { name: `Edit ${number}`, exact: true }).count(), 0);
    assert.equal(await page.getByRole('button', { name: `Posting ${number}`, exact: true }).count(), 0);
    await screenshot('stock-posted-desktop');
    await view('Saldo stok');
    await page.getByText('8 Unit', { exact: true }).waitFor();
    await page.getByText('Rp 4.000.000', { exact: true }).waitFor();
    await screenshot('stock-balance-desktop');
    await choose('Filter gudang', 'WH-SECONDARY - Gudang Tambahan');
    await page.getByText('Tidak ada data stok.', { exact: true }).waitFor();
    await choose('Filter gudang', 'Semua gudang');
    await view('Kartu stok');
    await page.getByText('8 Unit', { exact: true }).waitFor();
    await page.setViewportSize({ width: 1024, height: 768 }); await screenshot('stock-card-tablet-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await screenshot('stock-card-tablet-portrait');
    await page.setViewportSize({ width: 1440, height: 1000 });
    await view('Stok awal');
    await page.keyboard.press('Control+Shift+N'); await page.getByRole('dialog').waitFor(); await fillDraft();
    await page.getByRole('button', { name: 'Simpan draft', exact: true }).click(); await settle();
    number = (await page.locator('tbody tr').first().locator('td').first().innerText()).trim();
    await page.getByRole('button', { name: `Posting ${number}`, exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Posting', exact: true }).click();
    await page.getByText('Barang sudah memiliki histori stok di gudang ini. Stok awal hanya boleh sekali.', { exact: true }).waitFor();
    await screenshot('stock-second-opening-error'); await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click(); await screenshot('stock-sidebar-collapsed');
    await page.keyboard.press('Control+k');
    const navigation = page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).pathname === '/inventory/stock');
    await page.getByRole('dialog').getByRole('button', { name: /Stok/ }).click();
    await navigation;
    await page.waitForLoadState('networkidle');
    await settle();
    await page.getByRole('heading', { name: 'Stok', exact: true }).waitFor();
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await page.locator('html.dark').waitFor(); await screenshot('stock-dark-desktop');
    checks.push('Draft CRUD/restore, cost validation, exact value, posting immutable, duplicate guard, filter/ledger/shortcut/palette/theme PASS');
    for (const role of ['viewer', 'editor']) {
        const isolated = await browser.newContext({ viewport: { width: 1024, height: 768 }, timezoneId: 'Asia/Jakarta' });
        await isolated.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        const target = await isolated.newPage(); monitor(target); await login(target, role);
        assert.equal(await target.getByRole('button', { name: /^Tambah stok awal$/ }).count(), role === 'viewer' ? 0 : 1);
        assert.equal(await target.getByRole('button', { name: /^Posting SA-/ }).count(), 0);
        if (role === 'viewer') assert.equal(await target.getByRole('button', { name: /^(Edit|Hapus) SA-/ }).count(), 0);
        await target.screenshot({ path: path.join(artifacts, `stock-${role}-tablet.png`), fullPage: true, animations: 'disabled' });
        checks.push(`${role}: permission UX PASS`); await isolated.close();
    }
    assert.deepEqual(errors, [], 'Console/page errors');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page && await page.getByRole('dialog').isVisible()) {
        await page.screenshot({ path: path.join(artifacts, 'stock-failure.png'), fullPage: true });
    }
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
}
