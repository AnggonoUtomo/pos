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
const artifacts = path.join(root, 'storage/framework/testing', `suppliers-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const value = socket.address().port; socket.close(() => resolve(value)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `suppliers_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = (input) => {
    const result = spawnSync('php', ['tests/Browser/suppliers-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, 'Fixture/persistence check gagal; periksa PHP helper.');
};
const password = randomBytes(32).toString('hex');
fixture({ password });
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true,
});
let browser; let page;
const checks = []; const errors = [];
try {
    for (let i = 0; i < 80; i++) {
        try { if ((await fetch(`${url}/login`)).ok) break; } catch { /* Await disposable server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const open = async () => {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
        const target = await context.newPage();
        target.on('pageerror', (e) => errors.push(e.message));
        target.on('console', (m) => { if (['error', 'warning'].includes(m.type())) errors.push(m.text()); });
        return { context, target };
    };
    const login = async (target, role) => {
        await target.goto(`${url}/login`);
        await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click();
        await target.waitForURL('**/dashboard');
    };
    const settle = async () => {
        await page.getByRole('dialog').waitFor({ state: 'detached' });
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
    const lifecycle = async (name, operation) => {
        await page.getByRole('button', { name: `${operation} ${name}`, exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: operation, exact: true }).click();
        await settle();
    };
    ({ target: page } = await open()); await login(page, 'manager');
    await page.goto(`${url}/purchasing/suppliers`);
    await page.getByRole('heading', { name: 'Supplier', exact: true }).waitFor();
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('qa-cctv');
    await page.getByLabel('Nama', { exact: true }).fill('Supplier QA');
    await page.getByLabel('Email', { exact: true }).fill('private-qa@example.test');
    await page.getByLabel('Telepon', { exact: true }).fill('0800-QA-PRIVATE');
    await page.getByLabel('Alamat', { exact: true }).fill('Alamat privat QA');
    for (const [width, height] of [[1024, 768], [768, 1024]]) {
        await page.setViewportSize({ width, height }); await shot(`form-${width}`);
    }
    await page.keyboard.press('Control+Enter'); await settle();
    await page.getByRole('row').filter({ hasText: 'QA-CCTV' }).waitFor();
    await page.reload();
    await page.getByRole('button', { name: 'Edit Supplier QA', exact: true }).click();
    assert.equal(await page.getByLabel('Email', { exact: true }).inputValue(), 'private-qa@example.test');
    await page.getByLabel('Nama', { exact: true }).fill('Supplier QA Updated');
    await page.getByLabel('Aktif', { exact: true }).click();
    await page.keyboard.press('Control+Enter'); await settle();
    await lifecycle('Supplier QA Updated', 'Hapus');
    await page.getByRole('button', { name: 'Terhapus', exact: true }).click();
    await page.getByRole('button', { name: 'Pulihkan Supplier QA Updated', exact: true }).waitFor();
    await lifecycle('Supplier QA Updated', 'Pulihkan');
    await page.getByRole('button', { name: 'Data', exact: true }).click();
    const row = page.getByRole('row').filter({ hasText: 'QA-CCTV' }); await row.waitFor();
    assert((await row.innerText()).includes('Nonaktif'));
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Kode', { exact: true }).fill('QA-CCTV');
    await page.getByLabel('Nama', { exact: true }).fill('Duplicate');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.locator('#supplier-code[aria-invalid="true"]').waitFor();
    assert.equal(await page.getByLabel('Nama', { exact: true }).inputValue(), 'Duplicate');
    await page.getByRole('button', { name: 'Batal', exact: true }).click(); await settle();
    await page.getByRole('combobox', { name: 'Filter status', exact: true }).click();
    await page.getByRole('option', { name: 'Nonaktif', exact: true }).click();
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1);
    await page.keyboard.press('Alt+f');
    await page.getByLabel('Cari supplier', { exact: true }).fill('NO-MATCH');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.getByText('Tidak ada data.', { exact: true }).waitFor(); await shot('empty');
    await page.goto(`${url}/purchasing/suppliers`);
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click();
    await page.getByText('Shortcut Halaman', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await page.keyboard.press('Control+k');
    await page.getByRole('dialog').getByRole('button', { name: /^Supplier/ }).click(); await settle();
    for (const mode of ['Light', 'Dark']) {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.getByRole('button', { name: 'Tema', exact: true }).click();
        await page.getByRole('menuitem', { name: mode, exact: true }).click(); await page.keyboard.press('Escape');
        await page.waitForFunction((dark) => document.documentElement.classList.contains('dark') === dark, mode === 'Dark');
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await page.setViewportSize({ width, height });
            for (const collapsed of [false, true]) {
                const sidebar = page.locator('[data-sidebar="sidebar"]').first();
                const state = await sidebar.evaluate((e) => e.closest('[data-state]')?.getAttribute('data-state'));
                if ((state === 'collapsed') !== collapsed) await page.locator('[data-sidebar="trigger"]').first().click();
                await page.waitForTimeout(250);
                await shot(`${mode.toLowerCase()}-${width}-${collapsed ? 'collapsed' : 'expanded'}`);
            }
        }
    }
    for (const role of ['viewer', 'outsider']) {
        const { context, target } = await open(); await login(target, role);
        if (role === 'viewer') {
            await target.goto(`${url}/purchasing/suppliers`);
            await target.getByRole('heading', { name: 'Supplier', exact: true }).waitFor();
            assert.equal(await target.getByRole('button', { name: /^(Tambah supplier|Edit |Hapus |Pulihkan )/ }).count(), 0);
        } else {
            assert.equal(await target.getByRole('link', { name: /^Supplier$/ }).count(), 0);
            assert.equal((await context.request.get(`${url}/purchasing/suppliers/data`, { headers: { Accept: 'application/json' } })).status(), 403);
        }
        await target.keyboard.press('Control+k');
        assert.equal(await target.getByRole('dialog').getByRole('button', { name: /^Supplier/ }).count(), role === 'viewer' ? 1 : 0);
        await context.close(); checks.push(`${role}: permission UX/backend PASS`);
    }
    fixture({ verify: true });
    checks.push('CRUD, reload, inactive restore, duplicate/error, filters/empty, shortcuts/palette, persistence/audit privacy PASS');
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
