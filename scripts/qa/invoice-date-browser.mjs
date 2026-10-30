import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `mvp-browser-${id}`);
await mkdir(path.join(artifacts, 'views'), { recursive: true });
const database = `${artifacts}.sqlite`;
await writeFile(database, '');
const port = await new Promise((resolve) => {
    const socket = createServer();
    socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, APP_TIMEZONE: 'Asia/Jakarta',
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`, APP_CONFIG_CACHE: path.join(artifacts, 'config.php'),
    APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'), VIEW_COMPILED_PATH: path.join(artifacts, 'views'),
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: 'null', CACHE_STORE: 'array', SESSION_DRIVER: 'database',
    SESSION_COOKIE: `dates_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
const fixture = (input) => {
    const result = spawnSync('php', ['tests/Browser/mvp-checkout-fixture.php'], { cwd: root, env,
        input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, result.stderr); return JSON.parse(result.stdout);
};
const password = randomBytes(32).toString('hex');
const setup = fixture({ password, invoice_date_demo: true });
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
    { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
let browser;
const errors = [];
try {
    for (let i = 0; i < 80; i++) {
        try { if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) })).ok) break; } catch { /* Wait for isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, timezoneId: 'Asia/Jakarta' });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    const page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-manager@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await page.waitForURL('**/dashboard');
    await page.goto(`${url}/sales/invoices`);
    for (const [from, until, count] of [['2026-10-16', '2026-10-16', 2], ['2026-10-17', '', 1], ['', '2026-10-15', 0]]) {
        await page.getByLabel('Tanggal awal (WIB)', { exact: true }).fill(from);
        await page.getByLabel('Tanggal akhir (WIB)', { exact: true }).fill(until);
        const response = page.waitForResponse((r) => new URL(r.url()).pathname === '/sales/invoices' && r.request().headers()['x-inertia'] === 'true');
        await page.getByRole('button', { name: 'Terapkan', exact: true }).click();
        assert.equal((await response).status(), 200);
        await page.locator('section[aria-busy="false"]').waitFor();
        assert.equal(await page.getByTestId('invoice-row').count(), count);
        assert.equal(new URL(page.url()).searchParams.get('date_from') || '', from);
        await page.reload();
        assert.equal(await page.getByTestId('invoice-row').count(), count);
    }
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.getByRole('button', { name: 'Reset filter', exact: true }).click();
    await page.locator('section[aria-busy="false"]').waitFor();
    assert.equal(await page.getByTestId('invoice-row').count(), 3);
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.screenshot({ path: path.join(artifacts, 'invoice-date-tablet.png'), fullPage: true });
    fixture({ verify_invoice_dates: true, fingerprint: setup.fingerprint });
    assert.deepEqual(errors, []);
    console.log(JSON.stringify({ status: 'PASS', dates: 'WIB inclusive/exclusive, open ranges, Inertia URL/reload/reset',
        readonly: true, console: errors, artifacts: path.relative(root, artifacts) }));
} finally {
    if (browser) await browser.close();
    server.kill();
}
