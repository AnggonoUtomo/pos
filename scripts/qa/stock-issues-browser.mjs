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
const artifacts = path.join(root, 'storage/framework/testing', `stock-browser-${id}`);
const database = `${artifacts}.sqlite`;
await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const value = socket.address().port; socket.close(() => resolve(value)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `stock_issue_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/stock-fixture.php', '--issues'], { cwd: root, env, input: JSON.stringify({ password }), encoding: 'utf8', windowsHide: true });
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
    await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (msg) => { if (['error', 'warning'].includes(msg.type())) errors.push(msg.text()); });
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-viewer@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await page.waitForURL('**/dashboard');
    await page.goto(`${url}/inventory/stock?tab=movements`);
    await page.getByRole('heading', { name: 'Stok', exact: true }).waitFor();
    await page.getByRole('columnheader', { name: 'Masuk / keluar', exact: true }).waitFor();
    const sale = page.getByRole('row').filter({ hasText: 'POS-QA-ISSUE' });
    assert((await sale.innerText()).includes('-0,5'));
    assert((await sale.innerText()).includes('-500'));
    assert.equal(await page.locator('tbody tr').count(), 2);
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Body overflow');
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
        checks.push(`${name}: signed quantity/value, framing PASS`);
    };
    await shot('stock-issues-desktop');
    await page.getByRole('button', { name: 'Toggle Sidebar', exact: true }).click(); await shot('stock-issues-collapsed');
    for (const size of [{ width: 1024, height: 768 }, { width: 768, height: 1024 }]) {
        await page.setViewportSize(size); await shot(`stock-issues-${size.width}`);
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await page.locator('html.dark').waitFor();
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('stock-issues-dark');
    await page.getByLabel('Cari stok', { exact: true }).fill('POS-QA-ISSUE');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.waitForFunction(() => document.querySelectorAll('tbody tr').length === 1);
    assert((await page.locator('tbody').innerText()).includes('POS-QA-ISSUE'));
    await page.getByRole('button', { name: 'Saldo stok', exact: true }).click();
    await page.waitForURL((target) => target.searchParams.get('tab') === 'balances');
    await page.locator('section[aria-busy="false"]').waitFor();
    await page.getByLabel('Cari stok', { exact: true }).fill('');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await page.waitForFunction(() => document.querySelector('tbody')?.innerText.includes('9,5'));
    assert.equal(await page.getByRole('button', { name: /^(Tambah|Posting|Hapus|Edit|Pulihkan) / }).count(), 0);
    assert.deepEqual(errors, [], 'Console/page errors');
    checks.push('Sale dan opening legacy terlihat, search sumber, saldo 9,5 dan readonly UX PASS');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, error: error.message, console: errors }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); }
