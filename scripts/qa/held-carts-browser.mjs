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
const artifacts = path.join(root, 'storage/framework/testing', `held-carts-browser-${id}`);
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
    SESSION_COOKIE: `held_qa_${id}`,
    SESSION_DOMAIN: 'null',
    SESSION_SECURE_COOKIE: 'false',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
};
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], {
    cwd: root,
    env,
    input: JSON.stringify({ password, inventory_demo: true, checkout_demo: true }),
    encoding: 'utf8',
    windowsHide: true,
});
assert.equal(fixture.status, 0, fixture.stderr);
const verify = (...args) => {
    const result = spawnSync('php', ['tests/Browser/pos-checkout-verify.php', ...args], { cwd: root, env, encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, result.stderr);
    return args.length ? result.stdout : JSON.parse(result.stdout);
};
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
let expectedAbort = false;
try {
    for (let i = 0; i < 80; i++) {
        try {
            if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) })).ok) break;
        } catch {
            /* Await disposable server. */
        }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
    const attach = async () => {
        const target = await context.newPage();
        target.on('pageerror', (error) => errors.push(error.message));
        target.on('console', (message) => {
            if (message.type() === 'error' && !/status of 422/.test(message.text()) && !(expectedAbort && /net::ERR_FAILED/.test(message.text())))
                errors.push(message.text());
        });
        return target;
    };
    const choose = async (target, label, name) => {
        await target.getByLabel(label, { exact: true }).click();
        await target.getByRole('option', { name, exact: true }).click();
    };
    const prepare = async (target, quantity = '1') => {
        await choose(target, 'Gudang', 'Gudang Utama');
        await choose(target, 'Pelanggan', 'Installer CCTV Demo');
        await target.getByLabel('Cari barang', { exact: true }).fill('DEMO-CCTV-IP-2MP-UNIT');
        await target.locator('section[aria-label="Katalog barang"][aria-busy="false"]').waitFor();
        await target.getByLabel('Cari barang', { exact: true }).press('Enter');
        await target.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).fill(quantity);
    };
    const hold = async (target, label) => {
        await target.getByRole('dialog').waitFor({ state: 'detached' });
        await target.keyboard.press('F6');
        await target.getByLabel('Label keranjang (opsional)', { exact: true }).fill(label);
        await target.getByRole('button', { name: 'Simpan tertahan', exact: true }).click();
        await target.getByRole('dialog').waitFor({ state: 'hidden' });
        assert.equal(await target.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).count(), 0);
    };
    const recall = async (target, label) => {
        await target.getByRole('dialog').waitFor({ state: 'detached' });
        await target.getByRole('button', { name: 'Tertahan', exact: true }).click();
        await target.getByRole('button', { name: `Panggil ${label}`, exact: true }).click();
        await target.getByRole('dialog').waitFor({ state: 'hidden' });
        await target.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).waitFor();
    };
    const pay = async (target) => {
        await target.getByRole('dialog').waitFor({ state: 'detached' });
        await target.keyboard.press('F8');
        await target.getByLabel('Nominal 1', { exact: true }).waitFor();
        await target.getByRole('button', { name: 'Bayar dan posting', exact: true }).click();
    };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: overflow`);
        for (const dialog of await page.getByRole('dialog').all()) {
            if (!(await dialog.isVisible())) continue;
            const bounds = await dialog.boundingBox();
            const size = page.viewportSize();
            assert(
                bounds && bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= size.width + 1 && bounds.y + bounds.height <= size.height + 1,
                `${name}: framing`,
            );
        }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
    };
    page = await attach();
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-checkout@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await page.waitForURL('**/dashboard');
    await page.goto(`${url}/pos`);
    if (process.env.QA_HELD_FOCUS !== 'recovery') {
        await prepare(page, '2');
        await page.keyboard.press('F6');
        await shot('hold-desktop');
        await page.getByRole('button', { name: 'Batal', exact: true }).click();
        await hold(page, 'Pelanggan A');
        assert.equal(verify().invoices, 0);
        assert.equal(Number(verify().stock), 8);
        await page.reload();
        await page.getByRole('button', { name: 'Tertahan', exact: true }).click();
        await page.getByRole('button', { name: 'Panggil Pelanggan A', exact: true }).waitFor();
        await page.setViewportSize({ width: 1024, height: 768 });
        await shot('list-tablet');
        await page.keyboard.press('Escape');
        verify('--reprice');
        await recall(page, 'Pelanggan A');
        assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '2');
        await page.getByTestId('cart-subtotal').filter({ hasText: 'Rp 110.000' }).waitFor();
        await shot('recalled-tablet');
        checks.push('Hold without ledger, reload persistence, F6/F7 and current-price recall PASS');

        const second = await attach();
        await second.goto(`${url}/pos`);
        await recall(second, 'Pelanggan A');
        await pay(page);
        await page.getByRole('heading', { name: 'Bukti penjualan', exact: true }).waitFor();
        await page.getByRole('button', { name: 'Selesai', exact: true }).click();
        await pay(second);
        await second.getByRole('dialog').waitFor({ state: 'hidden' });
        await second.getByRole('alert').filter({ hasText: 'Keranjang tertahan' }).waitFor();
        assert.equal(verify().invoices, 1);
        assert.equal(verify().issues, 1);
        assert.equal(verify().receipts, 1);
        assert.equal(Number(verify().stock), 6);
        await second.close();
        checks.push('Two tabs: one checkout, stale held source rejected, stock deducted once PASS');
    }

    const baseline = verify();
    await prepare(page);
    await hold(page, 'Pelanggan B');
    await recall(page, 'Pelanggan B');
    expectedAbort = true;
    const payloads = [];
    page.on('request', (request) => {
        if (request.method() === 'POST' && request.url() === `${url}/pos/invoices`) payloads.push(request.postDataJSON());
    });
    await page.route(
        '**/pos/invoices',
        async (route) => {
            assert.equal((await route.fetch()).status(), 201);
            await route.abort('failed');
        },
        { times: 1 },
    );
    await pay(page);
    await page.getByRole('alert').filter({ hasText: 'Status pembayaran belum dapat dipastikan' }).waitFor();
    assert(
        await page
            .locator('header button')
            .filter({ hasText: /^Tertahan$/ })
            .isDisabled(),
    );
    page.once('dialog', (dialog) => dialog.accept());
    await page.reload();
    await page.getByRole('button', { name: 'Ulangi pembayaran', exact: true }).click();
    await page.getByRole('heading', { name: 'Bukti penjualan', exact: true }).waitFor();
    assert.deepEqual(payloads[0], payloads[1]);
    assert(payloads[0].held_cart);
    assert.equal(verify().invoices, baseline.invoices + 1);
    assert.equal(Number(verify().stock), Number(baseline.stock) - 1);
    await page.getByRole('button', { name: 'Selesai', exact: true }).click();
    expectedAbort = false;
    checks.push('Lost checkout response, reload with held source and exact replay without double payment PASS');

    await prepare(page);
    expectedAbort = true;
    const holdPayloads = [];
    page.on('request', (request) => {
        if (request.method() === 'POST' && /\/pos\/held-carts\/[^/]+$/.test(request.url()))
            holdPayloads.push({ url: request.url(), body: request.postDataJSON() });
    });
    await page.route(
        '**/pos/held-carts/*',
        async (route) => {
            if (route.request().method() !== 'POST') return route.continue();
            assert((await route.fetch()).ok());
            await route.abort('failed');
        },
        { times: 1 },
    );
    await page.getByRole('dialog').waitFor({ state: 'detached' });
    await page.keyboard.press('F6');
    await page.getByLabel('Label keranjang (opsional)', { exact: true }).fill('Pelanggan C');
    await page.getByRole('button', { name: 'Simpan tertahan', exact: true }).click();
    await page.getByRole('button', { name: 'Ulangi simpan', exact: true }).waitFor();
    assert(await page.getByLabel('Label keranjang (opsional)', { exact: true }).isDisabled());
    await page.getByRole('button', { name: 'Ulangi simpan', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    assert.deepEqual(holdPayloads[0], holdPayloads[1]);
    expectedAbort = false;
    checks.push('Lost hold response: frozen label, same ID/body retry without duplicate draft PASS');
    await recall(page, 'Pelanggan C');
    await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).fill('2');
    await hold(page, 'Pelanggan C');
    await recall(page, 'Pelanggan C');
    assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '2');
    await page.keyboard.press('F7');
    await page.getByRole('button', { name: 'Panggil Pelanggan C', exact: true }).click();
    await page.getByRole('dialog', { name: 'Ganti keranjang aktif?', exact: true }).waitFor();
    await page.getByRole('button', { name: 'Kembali', exact: true }).click();
    await page.getByRole('button', { name: 'Batalkan Pelanggan C', exact: true }).click();
    await page.getByRole('button', { name: 'Batalkan keranjang', exact: true }).click();
    await page.getByText('Belum ada keranjang tertahan.', { exact: true }).waitFor();
    await page.keyboard.press('Escape');
    assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).count(), 0);
    await page.reload();
    await page.getByRole('button', { name: 'Tertahan', exact: true }).click();
    await page.getByText('Belum ada keranjang tertahan.', { exact: true }).waitFor();
    assert.equal(verify().invoices, baseline.invoices + 1);
    assert.equal(Number(verify().stock), Number(baseline.stock) - 1);
    checks.push('Update same held cart, replace confirmation and cancel active source clear workspace without ledger PASS');
    assert.deepEqual(errors, []);
    const focus = process.env.QA_HELD_FOCUS === 'recovery' ? 'recovery' : 'full';
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'PASS', focus, checks, errors }, null, 2));
    console.log(JSON.stringify({ status: 'PASS', focus, checks, artifacts }, null, 2));
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
