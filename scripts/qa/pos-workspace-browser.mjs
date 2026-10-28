import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { checkCustomerAutocomplete } from './pos-customer-scenarios.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `pos-workspace-${id}`);
const database = path.join(root, 'storage/framework/testing', `parties-browser-${id}.sqlite`);
await mkdir(path.join(artifacts, 'framework/views'), { recursive: true });
await mkdir(path.join(artifacts, 'framework/cache'), { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer();
    socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => {
        const value = socket.address().port;
        socket.close(() => resolve(value));
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
    LARAVEL_STORAGE_PATH: artifacts,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'database',
    SESSION_COOKIE: `workspace_qa_${id}`,
    SESSION_DOMAIN: 'null',
    SESSION_SECURE_COOKIE: 'false',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
};
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], {
    cwd: root,
    env,
    input: JSON.stringify({ password, inventory_demo: true, checkout_demo: true, barcode_demo: true }),
    encoding: 'utf8',
    windowsHide: true,
});
assert.equal(fixture.status, 0, fixture.stderr);
const verify = () => {
    const result = spawnSync('php', ['tests/Browser/barcode-verify.php'], { cwd: root, env, encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, result.stderr);
    return JSON.parse(result.stdout);
};
const baseline = verify();
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'),
    env,
    stdio: 'ignore',
    windowsHide: true,
});
const checks = [];
const errors = [];
let browser;
let page;
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
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });
    const choose = async (label, name) => {
        await page.getByLabel(label, { exact: true }).click();
        await page.getByRole('option', { name, exact: true }).click();
    };
    const switchView = async (name) => {
        await page.getByRole('radio', { name: `Tampilan ${name}`, exact: true }).click();
        assert.equal(await page.locator('main').getAttribute('data-pos-view'), name === 'Visual' ? 'visual' : 'classic');
    };
    const shot = async (name) => {
        const metrics = await page.evaluate(() => {
            const catalog = document.querySelector('[data-testid="catalog-scroll"]').getBoundingClientRect();
            const search = document.querySelector('#pos-search').getBoundingClientRect();
            const cart = document.querySelector('[data-testid="cart-scroll"]').getBoundingClientRect();
            return {
                overflow: document.documentElement.scrollWidth > innerWidth,
                catalog: catalog.toJSON(),
                search: search.toJSON(),
                cart: cart.toJSON(),
                height: innerHeight,
            };
        });
        assert.equal(metrics.overflow, false, `${name}: horizontal overflow`);
        assert(metrics.catalog.height >= 220, `${name}: catalog too short (${metrics.catalog.height})`);
        assert(metrics.search.y >= 0 && metrics.search.bottom < metrics.height, `${name}: search outside viewport`);
        assert(metrics.cart.height >= 120, `${name}: cart too short (${metrics.cart.height})`);
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
        checks.push(
            `${name}: catalog ${Math.round(metrics.catalog.width)}x${Math.round(metrics.catalog.height)}, cart height ${Math.round(metrics.cart.height)} PASS`,
        );
    };
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-checkout@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await page.waitForURL('**/dashboard');
    await page.goto(`${url}/pos`);
    await page.getByRole('button', { name: 'Tambah Kamera IP 2MP', exact: true }).waitFor();
    assert.match(await page.getByLabel('Gudang', { exact: true }).innerText(), /Gudang Utama/);
    if (process.env.QA_POS_WORKSPACE_FOCUS === 'customer') {
        await checkCustomerAutocomplete(page, artifacts, checks);
    } else {
        assert.match(await page.getByLabel('Pelanggan', { exact: true }).inputValue(), /Pelanggan Umum/);
        assert.equal(await page.locator('main').getAttribute('data-pos-view'), 'visual');
        console.log('PASS: default Visual, Gudang Utama, pelanggan Eceran.');
        const scan = page.getByLabel('Scan barcode', { exact: true });
        await scan.fill('DEMO-CCTV-IP-2MP-UNIT');
        await scan.press('Enter');
        await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).waitFor();
        await scan.fill('DEMO-CCTV-IP-2MP-UNIT');
        await scan.press('Enter');
        await page.waitForFunction(() => document.querySelector('[aria-label="Kuantitas Kamera IP 2MP"]')?.value === '2');
        await page.getByLabel('Nilai diskon', { exact: true }).fill('1.000');
        await page.getByLabel('Pajak (%)', { exact: true }).fill('1');
        await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click();
        await page.getByText('Terperiksa', { exact: true }).waitFor();
        console.log('PASS: scan dan pemeriksaan keranjang.');
        const total = await page.getByTestId('cart-total').innerText();
        await shot('visual-desktop');
        await switchView('Klasik');
        assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '2');
        assert.equal(await page.getByLabel('Nilai diskon', { exact: true }).inputValue(), '1.000');
        assert.equal(await page.getByLabel('Pajak (%)', { exact: true }).inputValue(), '1');
        assert.equal(await page.getByTestId('cart-total').innerText(), total);
        await shot('classic-desktop');
        for (const size of [
            { width: 1024, height: 768 },
            { width: 768, height: 1024 },
        ]) {
            await page.setViewportSize(size);
            await shot(`classic-${size.width}`);
            await switchView('Visual');
            await shot(`visual-${size.width}`);
            await switchView('Klasik');
        }
        await page.getByRole('button', { name: 'Tahan', exact: true }).click();
        await page.getByLabel('Label keranjang (opsional)', { exact: true }).fill('QA dua tampilan');
        await page.getByRole('button', { name: 'Simpan tertahan', exact: true }).click();
        await page.getByRole('dialog').waitFor({ state: 'detached' });
        await page.reload();
        await page.getByRole('button', { name: 'Tertahan', exact: true }).click();
        assert.equal(await page.locator('main').getAttribute('data-pos-view'), 'classic');
        await page.getByRole('button', { name: 'Panggil QA dua tampilan', exact: true }).click();
        await page.getByRole('dialog').waitFor({ state: 'detached' });
        assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '2');
        assert.equal(await page.getByTestId('cart-total').innerText(), total);
        await switchView('Visual');
        await page.getByRole('button', { name: 'Pembayaran', exact: true }).click();
        await page.getByLabel('Nominal 1', { exact: true }).waitFor();
        await page.keyboard.press('Escape');
        await page.getByRole('dialog').waitFor({ state: 'detached' });
        await choose('Gudang', 'Gudang Tambahan');
        await choose('Pelanggan', 'Installer CCTV Demo');
        await switchView('Klasik');
        assert.match(await page.getByLabel('Gudang', { exact: true }).innerText(), /Gudang Tambahan/);
        assert.match(await page.getByLabel('Pelanggan', { exact: true }).inputValue(), /Installer CCTV Demo/);
        checks.push(
            'Default, scan burst, view switch preserves cart/pricing/quote, preference reload, hold/recall, payment dialog and manual context PASS',
        );
        await page.setViewportSize({ width: 640, height: 960 });
        await switchView('Visual');
        await page.getByRole('radio', { name: /Keranjang \(1\)/ }).click();
        assert(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).isVisible());
        await page.keyboard.press('Alt+f');
        await page.getByLabel('Cari barang', { exact: true }).waitFor({ state: 'visible' });
        assert(await page.getByLabel('Cari barang', { exact: true }).evaluate((node) => node === document.activeElement));
        await page.getByRole('radio', { name: /Keranjang \(1\)/ }).click();
        await page.getByRole('radio', { name: 'Barang', exact: true }).click();
        assert(await page.getByLabel('Cari barang', { exact: true }).isVisible());
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        await page.screenshot({ path: path.join(artifacts, 'visual-narrow.png'), fullPage: true });
        checks.push('Narrow tablet panel switching retains cart without overflow PASS');
    }
    assert.equal(verify().ledger, baseline.ledger, 'No stock/finance ledger changes');
    assert.deepEqual(errors, []);
    const report = { status: 'PASS', checks, errors, artifacts };
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, errors, error: error.message }, null, 2));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
    await new Promise((resolve) => {
        if (server.exitCode !== null) resolve();
        else server.once('exit', resolve);
    });
}
