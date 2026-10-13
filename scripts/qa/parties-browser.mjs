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
await mkdir(artifacts, { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `parties_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], { cwd: root, env, input: JSON.stringify({ password }), encoding: 'utf8' });
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
        try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Await isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    const monitor = (target) => {
        target.on('pageerror', (e) => errors.push(e.message));
        target.on('console', (msg) => { if (['error', 'warning'].includes(msg.type())) errors.push(msg.text()); });
    };
    monitor(page);
    const login = async (target, role) => {
        await target.goto(`${url}/login`);
        await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click();
        await target.waitForURL('**/dashboard');
    };
    const settle = async () => {
        await page.getByRole('dialog').waitFor({ state: 'hidden' });
        await page.locator('section[aria-busy="false"]').waitFor();
    };
    const shot = async (label) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${label}: overflow halaman`);
        const dialog = page.getByRole('dialog');
        if (await dialog.isVisible()) {
            const bounds = await dialog.boundingBox(); const size = page.viewportSize();
            assert(bounds && bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= size.width + 1 && bounds.y + bounds.height <= size.height + 1, `${label}: dialog framing`);
        }
        await page.screenshot({ path: path.join(artifacts, `${label}.png`), fullPage: true, animations: 'disabled' });
        checks.push(`${label}: framing PASS`);
    };
    const choose = async (label, name) => {
        await page.getByLabel(label, { exact: true }).click();
        const navigation = label === 'Filter level'
            ? page.waitForResponse((response) => response.request().method() === 'GET' && new URL(response.url()).pathname === '/sales/customers')
            : null;
        await page.getByRole('option', { name, exact: true }).click();
        if (navigation) await navigation;
    };
    const levelPage = async () => { await page.goto(`${url}/sales/customer-levels`); await page.getByRole('heading', { name: 'Level pelanggan', exact: true }).waitFor(); };
    const customerPage = async () => { await page.goto(`${url}/sales/customers`); await page.getByRole('heading', { name: 'Pelanggan', exact: true }).waitFor(); };
    const lifecycle = async (name, operation) => {
        await page.getByRole('button', { name: `${operation} ${name}`, exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: operation, exact: true }).click();
        await settle();
    };
    await login(page, 'manager');
    await levelPage(); await shot('levels-desktop');
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-LEVEL');
    await page.getByLabel('Nama', { exact: true }).fill('Level Installer QA');
    await page.keyboard.press('Control+Enter'); await settle();
    await page.getByRole('button', { name: 'Edit Level Installer QA', exact: true }).click();
    await page.getByLabel('Nama', { exact: true }).fill('Level QA');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click(); await settle();
    await lifecycle('Level QA', 'Hapus');
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Level QA', exact: true }).waitFor();
    await lifecycle('Level QA', 'Pulihkan');
    await customerPage();
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-CUSTOMER');
    await page.getByLabel('Nama', { exact: true }).fill('Installer Pelanggan QA');
    await choose('Level pelanggan', 'QA-LEVEL - Level QA');
    await page.getByLabel('Email', { exact: true }).fill('qa@example.test');
    await page.getByLabel('Telepon', { exact: true }).fill('DEMO-CONTACT');
    await page.getByLabel('Alamat', { exact: true }).fill('Alamat demo QA');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('customer-form-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('customer-form-portrait');
    await page.keyboard.press('Control+Enter'); await settle();
    await page.getByRole('button', { name: 'Edit Installer Pelanggan QA', exact: true }).waitFor();
    await page.reload(); await settle();
    await page.getByRole('button', { name: 'Edit Installer Pelanggan QA', exact: true }).click();
    await page.getByLabel('Nama', { exact: true }).fill('Installer QA Updated');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click(); await settle();
    await lifecycle('Installer QA Updated', 'Hapus');
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Installer QA Updated', exact: true }).waitFor();
    await shot('customer-deleted-portrait');
    await levelPage();
    await page.getByRole('button', { name: 'Hapus Level QA', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
    await page.getByRole('alert').filter({ hasText: 'Level sudah digunakan' }).waitFor();
    await shot('level-reference-error'); await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Edit Level QA', exact: true }).click();
    await page.getByLabel('Aktif', { exact: true }).click();
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.getByText('Level sudah digunakan pelanggan atau harga barang dan tidak boleh dihapus atau dinonaktifkan.', { exact: true }).first().waitFor();
    await page.keyboard.press('Escape');
    await customerPage();
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Installer QA Updated', exact: true }).waitFor();
    await lifecycle('Installer QA Updated', 'Pulihkan');
    await page.getByRole('button', { name: 'Data', exact: true }).click();
    await page.getByRole('button', { name: 'Edit Installer QA Updated', exact: true }).waitFor();
    await choose('Filter level', 'QA-LEVEL - Level QA');
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1);
    await page.getByRole('button', { name: 'Edit Installer QA Updated', exact: true }).waitFor();
    assert.equal(await page.locator('tbody tr').count(), 1);
    await choose('Filter level', 'Semua level');
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 4);
    await page.keyboard.press('Alt+f');
    await page.getByLabel('Cari pelanggan', { exact: true }).fill('NO-MATCH');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.getByText('Tidak ada data.', { exact: true }).waitFor();
    await shot('customer-empty-portrait');
    await customerPage();
    await page.setViewportSize({ width: 1440, height: 1000 }); await shot('customers-desktop');
    await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click(); await shot('customers-collapsed');
    await page.keyboard.press('Control+k');
    await page.getByRole('dialog').getByRole('button', { name: /^Level pelanggan/ }).click();
    await page.waitForURL('**/sales/customer-levels'); await settle();
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click();
    await page.getByText('Shortcut Halaman', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await page.locator('html.dark').waitFor(); await shot('levels-dark');
    // Verify the shared workspace default still navigates Inventory, not Sales.
    await page.goto(`${url}/inventory/units`);
    await page.getByLabel('Cari satuan', { exact: true }).fill('PCS');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.waitForURL('**/inventory/units?**');
    await page.getByRole('button', { name: 'Edit Buah', exact: true }).waitFor();
    checks.push('CRUD level/customer persisted, used+trashed reference guard, level filter, search/empty, shortcut/palette, Inventory route regression PASS');
    for (const role of ['viewer', 'outsider']) {
        const isolated = await browser.newContext({ viewport: { width: 1024, height: 768 } });
        await isolated.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await isolated.newPage(); monitor(target); await login(target, role);
        if (role === 'viewer') {
            await target.goto(`${url}/sales/customers`);
            await target.getByRole('heading', { name: 'Pelanggan', exact: true }).waitFor();
            assert.equal(await target.getByRole('button', { name: /^(Tambah pelanggan|Edit |Hapus |Pulihkan )/ }).count(), 0);
            await target.screenshot({ path: path.join(artifacts, 'customers-readonly-tablet.png'), fullPage: true });
        } else assert.equal(await target.getByRole('link', { name: /^Pelanggan/ }).count(), 0);
        await target.keyboard.press('Control+k');
        assert.equal(await target.getByRole('dialog').getByRole('button', { name: /^Pelanggan|^Level pelanggan/ }).count(), role === 'viewer' ? 2 : 0);
        checks.push(`${role}: permission UX PASS`); await isolated.close();
    }
    assert.deepEqual(errors, [], 'Console/page errors');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
}
