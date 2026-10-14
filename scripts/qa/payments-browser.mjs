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
const artifacts = path.join(root, 'storage/framework/testing', `payments-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const value = socket.address().port; socket.close(() => resolve(value)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `payments_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/payments-fixture.php'], { cwd: root, env, input: JSON.stringify({ password }), encoding: 'utf8', windowsHide: true });
assert.equal(fixture.status, 0, fixture.stderr);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true,
});
let browser; let page;
const checks = []; const errors = [];
try {
    for (let i = 0; i < 80; i++) {
        try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Await isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const monitor = async (targetContext) => {
        await targetContext.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await targetContext.newPage();
        target.on('pageerror', (error) => errors.push(error.message));
        target.on('console', (msg) => { if (['error', 'warning'].includes(msg.type())) errors.push(msg.text()); });
        return target;
    };
    page = await monitor(context);
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
    const navigate = async (kind) => {
        await page.goto(`${url}/finance/payment-${kind}`);
        await page.getByRole('heading', { name: kind === 'accounts' ? 'Akun kas dan bank' : 'Metode pembayaran', exact: true }).waitFor();
    };
    const choose = async (label, name) => {
        await page.getByLabel(label, { exact: true }).click();
        await page.getByRole('option', { name, exact: true }).click();
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
    const lifecycle = async (name, operation) => {
        await page.getByRole('button', { name: `${operation} ${name}`, exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: operation, exact: true }).click();
        await settle();
    };
    const save = async () => { await page.keyboard.press('Control+Enter'); await settle(); };
    await login(page, 'manager');
    await navigate('accounts'); await shot('accounts-desktop');
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-BANK');
    await page.getByLabel('Nama akun', { exact: true }).fill('Bank QA');
    await choose('Jenis akun', 'Bank');
    await page.getByLabel('Nama bank', { exact: true }).fill('Bank Demo QA');
    await page.getByLabel('Nomor rekening', { exact: true }).fill('0012345678');
    await page.getByLabel('Nama pemilik rekening', { exact: true }).fill('Pemilik Demo QA');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('account-form-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('account-form-portrait');
    await save();
    const bankRow = page.getByRole('row').filter({ hasText: 'QA-BANK' });
    await bankRow.waitFor(); assert((await bankRow.innerText()).includes('****5678'));
    assert(!(await bankRow.innerText()).includes('0012345678'));
    await page.reload();
    await page.getByRole('button', { name: 'Edit Bank QA', exact: true }).click();
    assert.equal(await page.getByLabel('Nomor rekening', { exact: true }).inputValue(), '0012345678');
    await page.getByLabel('Nama akun', { exact: true }).fill('Bank QA Updated'); await save();
    await navigate('methods');
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-TRANSFER');
    await page.getByLabel('Nama metode', { exact: true }).fill('Transfer QA');
    await choose('Akun tujuan', 'CASH-DEMO - Kas Toko Demo');
    await choose('Jenis metode', 'Transfer');
    assert(await page.getByRole('button', { name: 'Simpan', exact: true }).isDisabled());
    await page.getByLabel('Akun tujuan', { exact: true }).click();
    assert.equal(await page.getByRole('option', { name: /CASH-DEMO/ }).count(), 0);
    await page.getByRole('option', { name: 'QA-BANK - Bank QA Updated', exact: true }).click();
    await shot('method-form-portrait'); await save();
    await page.reload();
    await page.getByRole('button', { name: 'Edit Transfer QA', exact: true }).click();
    assert((await page.getByLabel('Akun tujuan', { exact: true }).innerText()).includes('QA-BANK'));
    await page.getByLabel('Nama metode', { exact: true }).fill('Transfer QA Updated'); await save();
    await lifecycle('Transfer QA Updated', 'Hapus');
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Transfer QA Updated', exact: true }).waitFor();
    await shot('methods-deleted-portrait');
    await navigate('accounts');
    await page.getByRole('button', { name: 'Hapus Bank QA Updated', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
    await page.getByRole('alert').filter({ hasText: 'Akun sudah digunakan' }).waitFor();
    await shot('account-reference-error'); await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Edit Bank QA Updated', exact: true }).click();
    await page.getByLabel('Aktif', { exact: true }).click();
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.getByText('Akun sudah digunakan metode pembayaran dan tidak boleh dihapus, dinonaktifkan, atau diganti jenisnya.', { exact: true }).first().waitFor();
    await page.getByRole('button', { name: 'Batal', exact: true }).click(); await settle();
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-CASH');
    await page.getByLabel('Nama akun', { exact: true }).fill('Kas QA');
    assert.equal(await page.getByLabel('Nomor rekening', { exact: true }).count(), 0);
    await save(); await lifecycle('Kas QA', 'Hapus');
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Kas QA', exact: true }).waitFor();
    await lifecycle('Kas QA', 'Pulihkan');
    await page.getByRole('button', { name: 'Data', exact: true }).click();
    await page.getByRole('button', { name: 'Edit Kas QA', exact: true }).waitFor();
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-CASH');
    await page.getByLabel('Nama akun', { exact: true }).fill('Duplikat QA');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.getByText('Kode sudah digunakan, termasuk pada data terhapus.', { exact: true }).waitFor();
    assert.equal(await page.getByLabel('Nama akun', { exact: true }).inputValue(), 'Duplikat QA');
    await page.getByRole('button', { name: 'Batal', exact: true }).click(); await settle();
    await choose('Jenis', 'Bank');
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 2);
    await choose('Jenis', 'Semua jenis');
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 4);
    await page.keyboard.press('Alt+f');
    await page.getByLabel('Cari akun kas dan bank', { exact: true }).fill('NO-MATCH');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.getByText('Tidak ada data.', { exact: true }).waitFor(); await shot('accounts-empty-portrait');
    await navigate('methods');
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Transfer QA Updated', exact: true }).waitFor();
    await lifecycle('Transfer QA Updated', 'Pulihkan');
    await page.getByRole('button', { name: 'Data', exact: true }).click();
    await page.getByRole('button', { name: 'Edit Transfer QA Updated', exact: true }).waitFor();
    await choose('Akun', 'QA-BANK - Bank QA Updated');
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1);
    await page.getByRole('button', { name: 'Edit Transfer QA Updated', exact: true }).waitFor();
    await navigate('methods');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('methods-landscape');
    await page.setViewportSize({ width: 1440, height: 1000 }); await shot('methods-desktop');
    await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click(); await shot('methods-collapsed');
    await page.keyboard.press('Control+k');
    await page.getByRole('dialog').getByRole('button', { name: /^Kas dan bank/ }).click();
    await page.waitForURL('**/finance/payment-accounts'); await settle();
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click();
    await page.getByText('Shortcut Halaman', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await page.locator('html.dark').waitFor(); await shot('accounts-dark');
    checks.push('CRUD/restore dua master, metadata conditional/masked, relasi reload/type reset, used+trashed guard, duplicate/error, filter/search/empty, shortcut/palette PASS');
    for (const role of ['viewer', 'outsider']) {
        const isolated = await browser.newContext({ viewport: { width: 1024, height: 768 } });
        const target = await monitor(isolated); await login(target, role);
        if (role === 'viewer') {
            await target.goto(`${url}/finance/payment-methods`);
            await target.getByRole('heading', { name: 'Metode pembayaran', exact: true }).waitFor();
            assert.equal(await target.getByRole('button', { name: /^(Tambah metode pembayaran|Edit |Hapus |Pulihkan )/ }).count(), 0);
            await target.screenshot({ path: path.join(artifacts, 'methods-readonly-tablet.png'), fullPage: true });
        } else {
            assert.equal(await target.getByRole('link', { name: /^(Kas dan bank|Metode pembayaran)$/ }).count(), 0);
            assert.equal((await isolated.request.get(`${url}/finance/payment-methods/data`, { headers: { Accept: 'application/json' } })).status(), 403);
        }
        await target.keyboard.press('Control+k');
        assert.equal(await target.getByRole('dialog').getByRole('button', { name: /^(Kas dan bank|Metode pembayaran)/ }).count(), role === 'viewer' ? 2 : 0);
        checks.push(`${role}: permission UX/backend PASS`); await isolated.close();
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
