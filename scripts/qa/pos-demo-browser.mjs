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
await mkdir(artifacts, { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `pos_demo_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], { cwd: root, env, input: JSON.stringify({ password, inventory_demo: true }), encoding: 'utf8' });
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
        try { if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(5000) })).ok) break; } catch { /* Await isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (msg) => { if (['error', 'warning'].includes(msg.type())) errors.push(msg.text()); });
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: overflow halaman`);
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
        checks.push(`${name}: framing PASS`);
    };
    const choose = async (label, name) => {
        await page.getByLabel(label, { exact: true }).click();
        await page.getByRole('option', { name, exact: true }).click();
    };
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-manager@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click(); await page.waitForURL('**/dashboard');
    await page.goto(`${url}/inventory/items`);
    await page.getByText('Kamera IP 2MP', { exact: true }).waitFor();
    assert.equal(await page.locator('tbody tr').count(), 9); await shot('demo-items-desktop');
    await page.goto(`${url}/inventory/units`);
    await page.getByText('Meter', { exact: true }).waitFor(); await page.getByText('Rol', { exact: true }).waitFor();
    await page.goto(`${url}/sales/customers`);
    await page.getByText('Installer CCTV Demo', { exact: true }).waitFor();
    await page.goto(`${url}/inventory/stock`);
    await page.getByRole('button', { name: 'Saldo stok', exact: true }).click();
    await page.getByText('Kabel NYM 3x1.5 mm2', { exact: true }).waitFor();
    assert.equal(await page.locator('tbody tr').count(), 5); await shot('demo-stock-desktop');
    await page.goto(`${url}/pos`);
    await page.getByRole('heading', { name: 'POS', exact: true }).waitFor();
    assert.equal(await page.getByRole('button', { name: /DEMO-(CCTV|ELEC)-/ }).count(), 9);
    assert.equal(await page.getByText('Beras Ramos 5 kg', { exact: true }).count(), 0);
    await shot('pos-cctv-desktop');
    await page.getByLabel('Cari Item', { exact: true }).fill('NYM');
    await page.getByRole('button', { name: /Kabel NYM 3x1.5 mm2/ }).click();
    await choose('Satuan Kabel NYM 3x1.5 mm2', 'Rol');
    await page.getByLabel('Qty Kabel NYM 3x1.5 mm2', { exact: true }).waitFor();
    await choose('Level Pelanggan', 'Installer');
    await page.getByLabel('Cari Item', { exact: true }).fill('Kamera');
    await page.getByRole('button', { name: /Kamera IP 2MP.*DEMO-CCTV-IP-2MP/ }).click();
    await choose('Satuan Kamera IP 2MP', 'Dus');
    await page.getByLabel('Cari Item', { exact: true }).fill('');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('pos-cctv-landscape');
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('pos-cctv-portrait');
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click(); await page.keyboard.press('Escape');
    await page.locator('html.dark').waitFor();
    await page.setViewportSize({ width: 768, height: 1024 }); await shot('pos-cctv-dark-portrait');
    await page.getByRole('button', { name: 'Reset', exact: true }).click();
    await page.getByText('Keranjang masih kosong.', { exact: true }).waitFor();
    assert.deepEqual(errors, [], 'Console/page errors');
    const report = { status: 'PASS', checks, console: errors, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'pos-demo-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'pos-demo-failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'pos-demo-report.json'), JSON.stringify({ status: 'FAIL', checks, console: errors, error: error.message }, null, 2));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
}
