import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const modulePath = process.env.QA_PLAYWRIGHT_MODULE;
assert(modulePath, 'Set QA_PLAYWRIGHT_MODULE ke package Playwright yang sudah terpasang; script tidak memasang dependency.');
const { chromium } = createRequire(import.meta.url)(modulePath);
const runId = randomBytes(8).toString('hex');
const artifactDir = path.join(root, 'storage/framework/testing', `inventory-browser-${runId}`);
const database = path.join(root, 'storage/framework/testing', `inventory-browser-${runId}.sqlite`);
await mkdir(artifactDir, { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer();
    socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const selected = socket.address().port; socket.close(() => resolve(selected)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database',
    SESSION_COOKIE: `inventory_qa_${runId}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/inventory-fixture.php'], { cwd: root, env, input: JSON.stringify({ password }), encoding: 'utf8' });
assert.equal(fixture.status, 0, `Fixture gagal: ${fixture.stderr}`);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore' });
let browser;
const failures = [];
const checks = [];
try {
    for (let attempt = 0; attempt < 80; attempt++) {
        try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Wait for the disposable server to become ready. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    page.on('pageerror', (error) => failures.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error' || message.type() === 'warning') failures.push(message.text()); });
    const login = async (target, email) => {
        await target.goto(`${url}/login`);
        await target.getByLabel('Email address').fill(email);
        await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click();
        await target.waitForURL('**/dashboard');
    };
    const settle = async () => {
        await page.locator('section[aria-busy="false"]').waitFor();
        await page.locator('[role="dialog"]').waitFor({ state: 'hidden' });
    };
    const checkFraming = async (label) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), `${label}: overflow halaman`);
        await page.screenshot({ path: path.join(artifactDir, `${label}.png`), fullPage: true });
        checks.push(`${label}: framing PASS`);
    };
    await login(page, 'qa-manager@example.test');
    await page.goto(`${url}/settings/profile`);
    await page.getByLabel('Name', { exact: true }).fill('QA Manager Verified');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await page.getByText('Profil diperbarui', { exact: true }).waitFor();
    await page.reload();
    assert.equal(await page.getByLabel('Name', { exact: true }).inputValue(), 'QA Manager Verified');
    checks.push('Authenticated profile update/reload PASS');
    for (const [module, label, withSymbol] of (process.argv.includes('--guard-only') || process.argv.includes('--items-only') ? [] : [['warehouses', 'Gudang', false], ['units', 'Satuan', true]])) {
        await page.goto(`${url}/inventory/${module}`);
        await page.getByRole('heading', { name: label, exact: true }).waitFor();
        await checkFraming(`${module}-desktop-expanded`);
        await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click();
        await checkFraming(`${module}-desktop-collapsed`);
        await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click();
        await page.getByRole('button', { name: 'Shortcut', exact: true }).click();
        await page.getByText('Shortcut Halaman', { exact: true }).waitFor();
        await page.keyboard.press('Escape');
        await page.keyboard.press('Control+Shift+N');
        await page.getByRole('dialog').waitFor();
        await page.getByLabel('Kode', { exact: true }).fill(`QA-${module}`);
        await page.getByLabel('Nama', { exact: true }).fill(`QA ${label}`);
        if (withSymbol) await page.getByLabel('Simbol', { exact: true }).fill('qa');
        await page.keyboard.press('Control+Enter');
        await settle();
        await page.getByRole('button', { name: `Edit QA ${label}`, exact: true }).click();
        await page.getByLabel('Nama', { exact: true }).fill(`QA ${label} Diubah`);
        await page.getByRole('button', { name: 'Simpan', exact: true }).click();
        await settle();
        await page.getByRole('button', { name: `Hapus QA ${label} Diubah`, exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
        await settle();
        await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
        await page.getByRole('button', { name: `Pulihkan QA ${label} Diubah`, exact: true }).waitFor();
        await page.getByRole('button', { name: `Pulihkan QA ${label} Diubah`, exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Pulihkan', exact: true }).click();
        await settle();
        await page.getByText(`Tidak ada data ${label.toLowerCase()}.`, { exact: true }).waitFor();
        await page.getByRole('button', { name: 'Data', exact: true }).click();
        await page.getByRole('button', { name: `Edit QA ${label} Diubah`, exact: true }).waitFor();
        await page.keyboard.press('Alt+f');
        await page.getByRole('textbox', { name: `Cari ${label.toLowerCase()}`, exact: true }).fill('NO-MATCH');
        await page.keyboard.press('Enter');
        await page.getByText(`Tidak ada data ${label.toLowerCase()}.`, { exact: true }).waitFor();
        await page.goto(`${url}/inventory/${module}`);
        await page.setViewportSize({ width: 1024, height: 768 });
        await checkFraming(`${module}-tablet-landscape`);
        await page.setViewportSize({ width: 768, height: 1024 });
        await checkFraming(`${module}-tablet-portrait`);
        await page.setViewportSize({ width: 1440, height: 1000 });
        checks.push(`${module}: CRUD, restore, search, empty, keyboard PASS`);
    }
    if (!process.argv.includes('--guard-only') && !process.argv.includes('--masters-only')) {
        await page.goto(`${url}/inventory/items`);
        await page.getByRole('heading', { name: 'Barang', exact: true }).waitFor();
        await checkFraming('items-desktop-expanded');
        await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click();
        await checkFraming('items-desktop-collapsed');
        await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click();
        await page.keyboard.press('Control+Shift+N');
        await page.getByLabel('SKU', { exact: true }).fill('QA-ITEM');
        await page.getByLabel('Nama barang', { exact: true }).fill('QA Barang');
        await page.getByLabel('Satuan dasar', { exact: true }).click();
        await page.getByRole('option', { name: 'Buah (pcs)', exact: true }).click();
        assert(await page.getByRole('textbox', { name: 'Rasio baris 1', exact: true }).getAttribute('readonly') !== null);
        await page.getByRole('textbox', { name: 'Barcode baris 1', exact: true }).fill('0');
        await page.getByRole('button', { name: 'Tambah satuan', exact: true }).click();
        await page.getByRole('combobox', { name: 'Satuan baris 2', exact: true }).click();
        await page.getByRole('option', { name: 'Dus (dus)', exact: true }).click();
        await page.getByRole('textbox', { name: 'Rasio baris 2', exact: true }).fill('0.5');
        await page.getByRole('textbox', { name: 'Barcode baris 2', exact: true }).fill('QA-ITEM-BOX');
        await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
        await page.getByText('Rasio minimal 1 karena base unit adalah satuan terkecil.', { exact: true }).waitFor();
        await page.getByRole('textbox', { name: 'Rasio baris 2', exact: true }).fill('12');
        await page.keyboard.press('Control+Enter');
        await settle();
        await page.getByRole('button', { name: 'Edit QA Barang', exact: true }).click();
        assert(await page.getByLabel('Satuan dasar', { exact: true }).isDisabled());
        const ratioIndex = (await page.getByRole('textbox', { name: 'Barcode baris 1', exact: true }).inputValue()) === 'QA-ITEM-BOX' ? 1 : 2;
        const baseIndex = ratioIndex === 1 ? 2 : 1;
        assert.equal(await page.getByRole('textbox', { name: `Barcode baris ${baseIndex}`, exact: true }).inputValue(), '0');
        await page.getByLabel('Nama barang', { exact: true }).fill('QA Barang Diubah');
        await page.getByRole('textbox', { name: `Rasio baris ${ratioIndex}`, exact: true }).fill('24.000001');
        await page.getByRole('checkbox', { name: `Satuan jual baris ${ratioIndex}`, exact: true }).uncheck();
        await page.setViewportSize({ width: 1024, height: 768 });
        await checkFraming('items-form-tablet-landscape');
        await page.setViewportSize({ width: 768, height: 1024 });
        await checkFraming('items-form-tablet-portrait');
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
        await settle();
        await page.getByRole('button', { name: 'Edit QA Barang Diubah', exact: true }).click();
        assert.equal(await page.getByRole('textbox', { name: `Rasio baris ${ratioIndex}`, exact: true }).inputValue(), '24.000001');
        assert.equal(await page.getByRole('checkbox', { name: `Satuan jual baris ${ratioIndex}`, exact: true }).isChecked(), false);
        await page.keyboard.press('Escape');
        await page.getByRole('button', { name: 'Hapus QA Barang Diubah', exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
        await settle();
        await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
        await page.getByRole('button', { name: 'Pulihkan QA Barang Diubah', exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Pulihkan', exact: true }).click();
        await settle();
        await page.getByRole('button', { name: 'Data', exact: true }).click();
        await page.getByRole('textbox', { name: 'Cari barang', exact: true }).fill('QA-ITEM-BOX');
        await page.getByRole('button', { name: 'Cari', exact: true }).click();
        await page.waitForURL((current) => current.searchParams.get('search') === 'QA-ITEM-BOX');
        await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1);
        await page.getByRole('button', { name: 'Edit QA Barang Diubah', exact: true }).waitFor();
        assert.equal(await page.locator('tbody tr').count(), 1);
        await page.goto(`${url}/inventory/items`);
        await page.setViewportSize({ width: 1024, height: 768 });
        await checkFraming('items-tablet-landscape');
        await page.setViewportSize({ width: 768, height: 1024 });
        await checkFraming('items-tablet-portrait');
        await page.setViewportSize({ width: 1440, height: 1000 });
        checks.push('Items: CRUD/restore, barcode 0/search, exact ratio, flags, base locked, invalid ratio PASS');
    }
    await page.goto(`${url}/inventory/units`);
    await page.getByRole('button', { name: 'Hapus Buah', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
    try {
        await page.getByRole('alert').filter({ hasText: 'Satuan sudah digunakan' }).waitFor({ timeout: 10000 });
    } catch (error) {
        await page.screenshot({ path: path.join(artifactDir, 'guard-failure.png'), fullPage: true });
        console.log('Guard diagnostic:', await page.locator('body').innerText());
        throw error;
    }
    await page.getByRole('button', { name: 'Batal', exact: true }).click();
    checks.push('Unit referenced guard dan Sonner error PASS');
    await page.keyboard.press('Control+k');
    await page.getByPlaceholder('Cari menu atau operasi...').fill('Gudang');
    await page.getByRole('dialog').getByRole('button', { name: /Gudang/ }).click();
    await page.waitForURL('**/inventory/warehouses');
    checks.push('Command palette navigation PASS');
    if (!process.argv.includes('--guard-only') && !process.argv.includes('--masters-only')) {
        await page.getByRole('button', { name: 'Tema', exact: true }).click();
        await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
        for (const module of ['warehouses', 'units', 'items']) {
            await page.goto(`${url}/inventory/${module}`);
            await page.locator('html.dark').waitFor();
            await checkFraming(`${module}-desktop-dark`);
        }
        await page.getByRole('button', { name: 'Tema', exact: true }).click();
        await page.getByRole('button', { name: 'Clean Default', exact: true }).click();
        await page.keyboard.press('Escape');
        await page.getByRole('menu').waitFor({ state: 'hidden' });
        await page.locator('html.dark[data-theme="clean"]').waitFor();
        await checkFraming('items-desktop-dark-clean');
        checks.push('Dark/clean theme integration tanpa perubahan CSS PASS');
    }
    const readonly = await browser.newContext({ viewport: { width: 1024, height: 768 } });
    const viewer = await readonly.newPage();
    viewer.on('pageerror', (error) => failures.push(error.message));
    viewer.on('console', (message) => { if (message.type() === 'error' || message.type() === 'warning') failures.push(message.text()); });
    await login(viewer, 'qa-viewer@example.test');
    for (const module of (process.argv.includes('--masters-only') || process.argv.includes('--guard-only') ? ['warehouses', 'units'] : ['warehouses', 'units', 'items'])) {
        await viewer.goto(`${url}/inventory/${module}`);
        assert.equal(await viewer.getByRole('button', { name: /^Tambah / }).count(), 0);
        assert.equal(await viewer.getByRole('button', { name: /^(Edit|Hapus) / }).count(), 0);
    }
    checks.push('Viewer readonly PASS');
    assert.deepEqual(failures, [], 'Console/page errors baru');
    const report = { status: 'PASS', checks, console: failures, artifacts: path.relative(root, artifactDir) };
    await writeFile(path.join(artifactDir, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} finally {
    if (browser) await browser.close();
    server.kill();
}
