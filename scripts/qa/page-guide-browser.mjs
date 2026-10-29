import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing, tanpa instalasi.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `mvp-browser-${id}`);
await mkdir(path.join(artifacts, 'views'), { recursive: true });
const database = `${artifacts}.sqlite`;
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer();
    socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => {
        const port = socket.address().port;
        socket.close(() => resolve(port));
    });
});
const url = `http://127.0.0.1:${port}`;
const env = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'false',
    APP_URL: url,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    APP_CONFIG_CACHE: path.join(artifacts, 'config.php'),
    APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'),
    VIEW_COMPILED_PATH: path.join(artifacts, 'views'),
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'database',
    SESSION_COOKIE: `guide_qa_${id}`,
    SESSION_DOMAIN: 'null',
    SESSION_SECURE_COOKIE: 'false',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
};
const fixture = (input) => {
    const result = spawnSync('php', ['tests/Browser/mvp-checkout-fixture.php'], {
        cwd: root,
        env,
        input: JSON.stringify(input),
        encoding: 'utf8',
        windowsHide: true,
    });
    assert.equal(result.status, 0, result.stderr);
    return JSON.parse(result.stdout);
};
const password = randomBytes(32).toString('hex');
const setup = fixture({ password, dashboard_only: true });
const manifest = JSON.parse(await readFile(path.join(root, 'public/build/manifest.json'), 'utf8'));
const guideFiles = Object.entries(manifest)
    .filter(([key]) => key.endsWith('.guide.ts'))
    .map(([, value]) => `/build/${value.file}`);
const dashboardGuide = `/build/${manifest['resources/js/pages/dashboard.guide.ts'].file}`;
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'),
    env,
    stdio: 'ignore',
    windowsHide: true,
});
const checks = [];
const errors = [];
let page;
let browser;
let expectedFailure = false;
try {
    for (let i = 0; i < 80; i++) {
        try {
            const response = await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) });
            await response.text();
            if (response.ok) break;
        } catch {
            /* Await isolated server. */
        }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error' && !expectedFailure) errors.push(message.text());
    });
    const requests = [];
    page.on('request', (request) => requests.push({ path: new URL(request.url()).pathname, method: request.method(), type: request.resourceType() }));
    const trigger = () => page.getByRole('button', { name: 'Panduan halaman', exact: true });
    const sheet = () => page.getByRole('dialog');
    const openGuide = async (title) => {
        await trigger().click();
        await sheet().getByRole('heading', { name: title, exact: true }).waitFor();
    };
    const closeGuide = async () => {
        await page.keyboard.press('Escape');
        await sheet().waitFor({ state: 'detached' });
    };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: page overflow`);
        await page.waitForFunction(() => {
            const box = document.querySelector('[role="dialog"]')?.getBoundingClientRect();
            return box && Math.abs(box.right - innerWidth) <= 1 && box.left >= 0;
        });
        const box = await sheet().boundingBox();
        const size = page.viewportSize();
        assert(
            box && box.x >= 0 && box.y >= 0 && Math.abs(box.x + box.width - size.width) <= 1 && box.y + box.height <= size.height + 1,
            `${name}: sheet framing`,
        );
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), animations: 'disabled' });
    };
    await page.goto(`${url}/login`);
    assert.equal(await trigger().count(), 0);
    await page.getByLabel('Email address').fill('qa-manager@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await page.waitForURL('**/dashboard');
    await trigger().waitFor();
    if (!process.env.QA_GUIDE_CONTINUE) {
        assert(!requests.some((request) => guideFiles.includes(request.path)), 'Guide chunks harus lazy.');
        await trigger().hover();
        await page.getByRole('tooltip').waitFor();
        await page.mouse.move(500, 100);
        const start = requests.length;
        await page.keyboard.press('F1');
        await sheet().getByRole('heading', { name: 'Panduan Dasbor', exact: true }).waitFor();
        await shot('dashboard-guide-desktop');
        await closeGuide();
        assert(await trigger().evaluate((node) => node === document.activeElement));
        assert(
            !requests.slice(start).some((request) => request.type === 'document' || request.method !== 'GET'),
            'Membuka panduan tidak reload/mutasi.',
        );
        assert.equal(page.url(), `${url}/dashboard`);
        checks.push('Login without guide; lazy content; F1/Escape, focus return, no reload/mutation PASS');

        await page.getByRole('link', { name: 'Satuan', exact: true }).first().click();
        await page.waitForURL('**/inventory/units');
        await openGuide('Panduan Satuan');
        await closeGuide();
        await page.setViewportSize({ width: 1024, height: 768 });
        await page.goto(`${url}/inventory/items`);
        await openGuide('Panduan Barang');
        const region = sheet().getByRole('region', { name: 'Isi panduan', exact: true });
        await region.hover();
        await page.mouse.wheel(0, 900);
        await page.waitForFunction(() => document.querySelector('[aria-label="Isi panduan"]').scrollTop > 0);
        await shot('items-guide-tablet');
        await closeGuide();
        checks.push('Inertia navigation selects correct guide; long guide scrolls independently PASS');

        await page.goto(`${url}/settings/profile`);
        await page.getByLabel('Nama', { exact: true }).fill('Isian belum disimpan QA');
        await openGuide('Panduan Profil Akun');
        await closeGuide();
        assert.equal(await page.getByLabel('Nama', { exact: true }).inputValue(), 'Isian belum disimpan QA');
        checks.push('Unsaved profile form retained without save PASS');
    }

    if (process.env.QA_GUIDE_CONTINUE !== 'finance') {
        await page.goto(`${url}/pos`);
        await page.getByRole('button', { name: 'Tambah Kamera IP 2MP', exact: true }).waitFor();
        const scanner = page.getByLabel('Scan barcode', { exact: true });
        await scanner.fill('DEMO-CCTV-IP-2MP-UNIT');
        await scanner.press('Enter');
        const quantity = page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true });
        await quantity.waitFor();
        await quantity.fill('2');
        for (const view of ['Klasik', 'Visual']) {
            await page.getByRole('radio', { name: `Tampilan ${view}`, exact: true }).click();
            await openGuide('Panduan POS');
            await closeGuide();
            assert.equal(await quantity.inputValue(), '2');
            assert.equal(await page.locator('[data-page-guide-trigger="floating"]').count(), 0);
        }
        await page.setViewportSize({ width: 1024, height: 768 });
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await openGuide('Panduan POS');
        await shot('pos-guide-tablet');
        assert.equal(await sheet().evaluate((node) => getComputedStyle(node).animationName), 'none');
        await closeGuide();
        assert.equal(await quantity.inputValue(), '2');
        assert(await page.getByRole('button', { name: 'Tambah Kamera IP 2MP', exact: true }).isVisible());
        checks.push('Both POS views retain scanned cart; inline toolbar, tablet framing and reduced motion PASS');
    }

    await page.setViewportSize({ width: 1024, height: 768 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.addInitScript(() => localStorage.setItem('appearance', 'dark'));
    await page.goto(`${url}/finance/cashbook`);
    await openGuide('Panduan Buku Kas');
    await shot('cashbook-guide-dark-tablet');
    await closeGuide();
    checks.push('Cashbook contextual content and dark/tablet PASS');

    // A fresh page has no cached import for the failure/retry check.
    await page.goto(`${url}/dashboard`);
    expectedFailure = true;
    await page.route(`**${dashboardGuide}`, (route) => route.fulfill({ status: 503, contentType: 'text/javascript', body: '' }), { times: 1 });
    await trigger().click();
    await sheet().getByRole('button', { name: 'Coba lagi', exact: true }).waitFor();
    await sheet().getByRole('button', { name: 'Coba lagi', exact: true }).click();
    await sheet().getByRole('heading', { name: 'Panduan Dasbor', exact: true }).waitFor();
    await closeGuide();
    expectedFailure = false;
    checks.push('Guide load failure shows retry and recovers without page reload PASS');

    assert.deepEqual(errors, []);
    assert.equal(fixture({ verify_dashboard: true, fingerprint: setup.fingerprint }).status, 'PASS');
    const report = { status: 'PASS', checks, errors, artifacts };
    await writeFile(path.join(artifacts, 'guide-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'guide-report.json'), JSON.stringify({ status: 'FAIL', checks, errors, message: error.message }, null, 2));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
    await new Promise((resolve) => {
        if (server.exitCode !== null) resolve();
        else server.once('exit', resolve);
    });
}
