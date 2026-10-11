import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Set QA_PLAYWRIGHT_MODULE ke package yang sudah terpasang.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const runId = randomBytes(8).toString('hex');
const artifactDir = path.join(root, 'storage/framework/testing', `identity-browser-${runId}`);
const database = `${artifactDir}.sqlite`;
await mkdir(artifactDir, { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer();
    socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database',
    SESSION_COOKIE: `identity_qa_${runId}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/identity-fixture.php'], { cwd: root, env, input: JSON.stringify({ password }), encoding: 'utf8' });
assert.equal(fixture.status, 0, `Fixture gagal: ${fixture.stderr}`);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore' });
let browser;
const checks = [];
const failures = [];
try {
    for (let attempt = 0; attempt < 80; attempt++) {
        try { if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) })).ok) break; } catch { /* Wait for loopback server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const watch = (page) => {
        page.on('pageerror', (error) => failures.push(error.message));
        page.on('console', (message) => { if (['error', 'warning'].includes(message.type())) failures.push(message.text()); });
        page.setDefaultTimeout(30000);
    };
    const login = async (page, key, destination = '/platform/identity') => {
        await page.goto(`${url}/login`, { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email address').fill(`qa-${key}@example.test`);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in', exact: true }).click();
        await page.waitForURL('**/dashboard');
        await page.goto(`${url}${destination}`);
        if (destination === '/platform/identity') {
            await page.getByRole('heading', { name: 'Identitas & Akses', exact: true }).waitFor();
        } else {
            await page.getByLabel('Name', { exact: true }).waitFor();
        }
    };
    const createContext = async (viewport) => {
        const context = await browser.newContext({ viewport });
        // External font availability is not part of this isolated feature QA.
        await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        return context;
    };
    const context = await createContext({ width: 1440, height: 1000 });
    const page = await context.newPage();
    watch(page);
    const settle = async () => {
        await page.getByRole('dialog').waitFor({ state: 'hidden' });
        await page.locator('section[aria-busy="false"]').waitFor();
    };
    const tab = async (name, key) => {
        await page.getByRole('tab', { name, exact: true }).click();
        await page.waitForURL(`**tab=${key}**`);
        await page.locator('section[aria-busy="false"]').waitFor();
    };
    const framing = async (label) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${label}: overflow halaman`);
        const dialog = page.getByRole('dialog');
        if (await dialog.count()) {
            const box = await dialog.boundingBox();
            assert(box && box.x >= 0 && box.y >= 0 && box.x + box.width <= page.viewportSize().width && box.y + box.height <= page.viewportSize().height + 1, `${label}: dialog di luar viewport`);
        }
        await page.screenshot({ path: path.join(artifactDir, `${label}.png`), fullPage: true, animations: 'disabled' });
        checks.push(`${label}: framing PASS`);
        console.log(`${label}: PASS`);
    };
    await login(page, 'manager');
    await framing('identity-desktop-expanded');
    await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click();
    await framing('identity-desktop-collapsed');
    await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click();
    await page.getByRole('button', { name: 'Shortcut', exact: true }).click();
    await page.getByText('Shortcut Halaman', { exact: true }).waitFor();
    await page.keyboard.press('Escape');
    assert.equal(await page.getByRole('button', { name: 'Edit role super-system', exact: true }).count(), 0);
    await page.keyboard.press('Control+Shift+N');
    await page.getByLabel('Nama role', { exact: true }).fill('qa-role');
    await page.keyboard.press('Control+Enter');
    await settle();
    await page.getByText('Role berhasil disimpan.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Edit role qa-role', exact: true }).click();
    await page.getByLabel('Nama role', { exact: true }).fill('qa-role-updated');
    await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
    await settle();
    await page.getByRole('button', { name: 'Permission qa-role-updated', exact: true }).click();
    await page.getByRole('checkbox', { name: 'inventory.view', exact: true }).check();
    for (const [width, height, label] of [[1024, 768, 'landscape'], [768, 1024, 'portrait']]) {
        await page.setViewportSize({ width, height });
        await framing(`identity-permission-tablet-${label}`);
    }
    await page.getByLabel('Cari pilihan akses').fill('NO-MATCH');
    await page.getByText('Tidak ada pilihan akses.', { exact: true }).waitFor();
    await page.getByLabel('Cari pilihan akses').fill('');
    await page.keyboard.press('Control+Enter');
    await settle();
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByRole('button', { name: 'Permission qa-role-updated', exact: true }).click();
    assert(await page.getByRole('checkbox', { name: 'inventory.view', exact: true }).isChecked());
    await page.keyboard.press('Escape');
    await tab('Akses Pengguna', 'users');
    await page.getByRole('button', { name: 'Atur role QA Super System', exact: true }).click();
    assert(await page.getByRole('checkbox', { name: 'super-system', exact: true }).isDisabled());
    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Atur role QA Staff', exact: true }).click();
    await page.getByRole('checkbox', { name: 'qa-role-updated', exact: true }).check();
    await page.keyboard.press('Control+Enter');
    await settle();
    await page.reload();
    await page.getByRole('button', { name: 'Atur role QA Staff', exact: true }).click();
    assert(await page.getByRole('checkbox', { name: 'qa-role-updated', exact: true }).isChecked());
    await page.keyboard.press('Escape');
    await tab('Role', 'roles');
    await page.getByRole('button', { name: 'Hapus role qa-role-updated', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
    await page.getByRole('alert').getByText('Role masih digunakan pengguna dan tidak dapat dihapus.', { exact: true }).waitFor();
    await page.keyboard.press('Escape');
    await tab('Akses Pengguna', 'users');
    await page.getByRole('button', { name: 'Atur role QA Staff', exact: true }).click();
    await page.getByRole('checkbox', { name: 'qa-role-updated', exact: true }).uncheck();
    await page.keyboard.press('Control+Enter');
    await settle();
    await tab('Role', 'roles');
    await page.getByRole('button', { name: 'Hapus role qa-role-updated', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click();
    await settle();
    assert.equal(await page.getByRole('button', { name: 'Edit role qa-role-updated', exact: true }).count(), 0);
    checks.push('Role CRUD, permission persistence, user role persistence, referenced-role/last-super guard, Sonner, keyboard PASS');
    await tab('Akses Pengguna', 'users');
    assert.equal(await page.getByRole('button', { name: 'Nonaktifkan QA Super System', exact: true }).count(), 0);
    await page.keyboard.press('Control+Shift+N');
    await page.getByRole('dialog').getByRole('heading', { name: 'Tambah pengguna', exact: true }).waitFor();
    for (const [width, height, label] of [[1440, 1000, 'desktop'], [1024, 768, 'tablet-landscape'], [768, 1024, 'tablet-portrait']]) {
        await page.setViewportSize({ width, height });
        await framing(`user-create-${label}`);
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByLabel('Nama pengguna', { exact: true }).fill('QA Created');
    await page.getByLabel('Email pengguna', { exact: true }).fill('qa-manager@example.test');
    await page.getByLabel('Password awal', { exact: true }).fill(password);
    await page.getByLabel('Konfirmasi password', { exact: true }).fill(password);
    await page.keyboard.press('Control+Enter');
    await page.getByRole('dialog').getByText('Email sudah digunakan.', { exact: true }).waitFor();
    assert.equal(await page.getByLabel('Password awal', { exact: true }).inputValue(), '');
    assert.equal(await page.getByLabel('Konfirmasi password', { exact: true }).inputValue(), '');
    await page.getByLabel('Email pengguna', { exact: true }).fill('qa-created@example.test');
    await page.getByLabel('Password awal', { exact: true }).fill(password);
    await page.getByLabel('Konfirmasi password', { exact: true }).fill(randomBytes(32).toString('hex'));
    await page.keyboard.press('Control+Enter');
    await page.locator('#user-password[aria-invalid="true"]').waitFor();
    assert.equal(await page.getByLabel('Password awal', { exact: true }).inputValue(), '');
    await page.getByLabel('Password awal', { exact: true }).fill(password);
    await page.getByLabel('Konfirmasi password', { exact: true }).fill(password);
    await page.getByRole('checkbox', { name: 'inventory-viewer', exact: true }).check();
    await page.keyboard.press('Control+Enter');
    await settle();
    await page.getByText('Pengguna berhasil disimpan.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Edit pengguna QA Created', exact: true }).click();
    assert.equal(await page.getByLabel('Password awal', { exact: true }).count(), 0);
    await page.getByLabel('Nama pengguna', { exact: true }).fill('QA Created Updated');
    await page.keyboard.press('Control+Enter');
    await settle();
    await page.reload();
    await page.getByRole('button', { name: 'Atur role QA Created Updated', exact: true }).click();
    assert(await page.getByRole('checkbox', { name: 'inventory-viewer', exact: true }).isChecked());
    await page.keyboard.press('Escape');
    const oldSessionContext = await createContext({ width: 1024, height: 768 });
    const oldSession = await oldSessionContext.newPage();
    watch(oldSession);
    await login(oldSession, 'created', '/settings/profile');
    await page.getByRole('button', { name: 'Nonaktifkan QA Created Updated', exact: true }).click();
    await framing('user-status-desktop');
    await page.keyboard.press('Control+Enter');
    await settle();
    const createdRow = page.getByRole('row').filter({ hasText: 'QA Created Updated' });
    await createdRow.getByText('Nonaktif', { exact: true }).waitFor();
    const freshContext = await createContext({ width: 1024, height: 768 });
    const freshPage = await freshContext.newPage();
    watch(freshPage);
    await freshPage.goto(`${url}/login`);
    await freshPage.getByLabel('Email address').fill('qa-created@example.test');
    await freshPage.getByLabel('Password', { exact: true }).fill(password);
    await freshPage.getByRole('button', { name: 'Log in', exact: true }).click();
    await freshPage.getByText('These credentials do not match our records.', { exact: true }).waitFor();
    assert(freshPage.url().endsWith('/login'));
    await page.getByRole('button', { name: 'Aktifkan QA Created Updated', exact: true }).click();
    await page.keyboard.press('Control+Enter');
    await settle();
    await createdRow.getByText('Aktif', { exact: true }).waitFor();
    await oldSession.goto(`${url}/dashboard`);
    await oldSession.waitForURL('**/login');
    await login(freshPage, 'created', '/settings/profile');
    assert.equal(await freshPage.getByLabel('Name', { exact: true }).inputValue(), 'QA Created Updated');
    await oldSessionContext.close();
    await freshContext.close();
    checks.push('User create/edit/role persisted, duplicate/confirmation validation, password cleared, status badge, inactive login, old-session revoked after reactivation, fresh login PASS');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
    await page.locator('html.dark').waitFor();
    await page.getByRole('button', { name: 'Tambah pengguna', exact: true }).click();
    await framing('user-create-desktop-dark');
    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Light', exact: true }).click();
    const operatorContext = await createContext({ width: 1024, height: 768 });
    const operator = await operatorContext.newPage();
    watch(operator);
    await login(operator, 'operator');
    assert.equal(await operator.getByRole('tab', { name: 'Role', exact: true }).count(), 0);
    assert.equal(await operator.getByRole('tab', { name: 'Permission', exact: true }).count(), 0);
    assert.equal(await operator.getByRole('button', { name: 'Edit pengguna QA Super System', exact: true }).count(), 0);
    assert.equal(await operator.getByRole('button', { name: 'Nonaktifkan QA Super System', exact: true }).count(), 0);
    await operator.getByRole('button', { name: 'Tambah pengguna', exact: true }).click();
    assert(await operator.getByRole('checkbox', { name: 'super-system', exact: true }).isDisabled());
    assert(await operator.getByRole('checkbox', { name: 'inventory-manager', exact: true }).isDisabled());
    assert(!(await operator.getByRole('checkbox', { name: 'inventory-viewer', exact: true }).isDisabled()));
    await operator.keyboard.press('Escape');
    await operator.keyboard.press('Control+k');
    await operator.getByPlaceholder('Cari menu atau operasi...').fill('Identitas');
    await operator.getByRole('dialog').getByRole('button', { name: /Identitas/ }).click();
    await operator.waitForURL('**/platform/identity');
    await operator.getByRole('tab', { name: 'Akses Pengguna', exact: true }).waitFor();
    await operatorContext.close();
    checks.push('Users-only navigation/palette and delegated account operator privilege UX PASS');
    await page.keyboard.press('Alt+f');
    await page.getByLabel('Cari akses').fill('NO-MATCH');
    await page.keyboard.press('Enter');
    await page.getByText('Tidak ada data akses.', { exact: true }).waitFor();
    await page.goto(`${url}/platform/identity`);
    await tab('Permission', 'permissions');
    assert.equal(await page.getByRole('button', { name: /^(Edit|Hapus|Tambah) / }).count(), 0);
    for (const [width, height, label] of [[1024, 768, 'landscape'], [768, 1024, 'portrait']]) {
        await page.setViewportSize({ width, height });
        await framing(`identity-tablet-${label}`);
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.keyboard.press('Control+k');
    await page.getByPlaceholder('Cari menu atau operasi...').fill('Identitas');
    await page.getByRole('dialog').getByRole('button', { name: /Identitas/ }).click();
    await page.waitForURL('**/platform/identity');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
    await page.locator('html.dark').waitFor();
    await framing('identity-desktop-dark');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('button', { name: 'Clean Default', exact: true }).click();
    await page.keyboard.press('Escape');
    await page.getByRole('menu').waitFor({ state: 'hidden' });
    await framing('identity-desktop-dark-clean');
    for (const key of ['viewer', 'delegate']) {
        const scoped = await createContext({ width: 1024, height: 768 });
        const target = await scoped.newPage();
        watch(target);
        await login(target, key);
        assert.equal(await target.getByRole('button', { name: 'Edit role super-system', exact: true }).count(), 0);
        if (key === 'viewer') {
            assert.equal(await target.getByRole('button', { name: /^(Tambah|Edit|Hapus|Permission) / }).count(), 0);
        } else {
            assert.equal(await target.getByRole('button', { name: 'Permission inventory-manager', exact: true }).count(), 0);
            await target.getByRole('tab', { name: 'Akses Pengguna', exact: true }).click();
            await target.getByRole('button', { name: 'Atur role QA Staff', exact: true }).click();
            assert(await target.getByRole('checkbox', { name: 'super-system', exact: true }).isDisabled());
            assert(await target.getByRole('checkbox', { name: 'inventory-manager', exact: true }).isDisabled());
            assert(!(await target.getByRole('checkbox', { name: 'inventory-viewer', exact: true }).isDisabled()));
        }
        await scoped.close();
    }
    checks.push('Readonly/delegated manager, search/empty, catalog readonly, palette, dark/clean PASS');
    await page.goto(`${url}/settings/profile`);
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Light', exact: true }).click();
    await page.getByRole('button', { name: 'Hapus akun', exact: true }).click();
    let dialog = page.getByRole('dialog');
    await dialog.getByLabel('Password', { exact: true }).fill(randomBytes(32).toString('hex'));
    await page.keyboard.press('Control+Enter');
    await dialog.locator('#delete-account-password[aria-invalid="true"]').waitFor();
    await page.getByText('Akun belum dihapus.', { exact: true }).waitFor();
    assert.equal(await dialog.getByLabel('Password', { exact: true }).inputValue(), '');
    await dialog.getByLabel('Password', { exact: true }).fill(password);
    await page.keyboard.press('Control+Enter');
    await dialog.getByRole('alert').getByText('Super-system terakhir tidak dapat dihapus. Tetapkan super-system lain terlebih dahulu.', { exact: true }).waitFor();
    assert.equal(await dialog.getByLabel('Password', { exact: true }).inputValue(), '');
    for (const [width, height, label] of [[1440, 1000, 'desktop'], [1024, 768, 'tablet-landscape'], [768, 1024, 'tablet-portrait']]) {
        await page.setViewportSize({ width, height });
        await framing(`account-blocked-${label}`);
    }
    await page.keyboard.press('Escape');
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    await page.setViewportSize({ width: 1440, height: 1000 });
    assert.equal((await page.goto(`${url}/dashboard`)).status(), 200);
    await page.goto(`${url}/settings/profile`);
    assert.equal(await page.getByLabel('Name', { exact: true }).inputValue(), 'QA Super System');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
    await page.getByRole('button', { name: 'Hapus akun', exact: true }).click();
    dialog = page.getByRole('dialog');
    assert.equal(await dialog.getByRole('alert').count(), 0);
    await framing('account-dialog-desktop-dark');
    await page.keyboard.press('Escape');
    const ordinary = await createContext({ width: 1024, height: 768 });
    const staff = await ordinary.newPage();
    watch(staff);
    await login(staff, 'staff', '/settings/profile');
    await staff.getByRole('button', { name: 'Hapus akun', exact: true }).click();
    await staff.getByRole('dialog').getByLabel('Password', { exact: true }).fill(password);
    await staff.keyboard.press('Control+Enter');
    await staff.waitForURL(`${url}/`);
    await staff.getByText('Akun berhasil dihapus.', { exact: true }).waitFor();
    await staff.goto(`${url}/settings/profile`);
    await staff.waitForURL('**/login');
    await ordinary.close();
    checks.push('Account deletion: wrong password, last-super blocked/still authenticated, cleared password/errors, ordinary delete/logout, Sonner/keyboard PASS');
    assert.deepEqual(failures, [], 'Console/page errors baru');
    const report = { status: 'PASS', checks, console: failures, externalFonts: 'Stub CSS; fallback font dipakai', artifacts: path.relative(root, artifactDir) };
    await writeFile(path.join(artifactDir, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} finally {
    if (browser) await browser.close();
    server.kill();
}
