import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan Playwright existing.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `barcode-browser-${id}`);
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
    SESSION_COOKIE: `barcode_qa_${id}`,
    SESSION_DOMAIN: 'null',
    SESSION_SECURE_COOKIE: 'false',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
};
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], {
    cwd: root,
    env,
    input: JSON.stringify({ password, inventory_demo: true, barcode_demo: true }),
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
            /* Await disposable server. */
        }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    await context.addInitScript(() => {
        window.__prints = 0;
        window.print = () => {
            window.__prints++;
        };
    });
    page = await context.newPage();
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error' && !/status of 422/.test(message.text())) errors.push(message.text());
    });
    const choose = async (label, name) => {
        await page.getByLabel(label, { exact: true }).click();
        await page.getByRole('option', { name, exact: true }).click();
    };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: overflow`);
        for (const dialog of await page.getByRole('dialog').all()) {
            if (!(await dialog.isVisible())) continue;
            const box = await dialog.boundingBox();
            const viewport = page.viewportSize();
            assert(
                box && box.x >= 0 && box.y >= 0 && box.x + box.width <= viewport.width + 1 && box.y + box.height <= viewport.height + 1,
                `${name}: framing`,
            );
        }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
    };
    await page.goto(`${url}/login`);
    await page.getByLabel('Email address').fill('qa-manager@example.test');
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await page.waitForURL('**/dashboard');
    let internal = 'DEMO-CCTV-IP-2MP-UNIT';
    let manufacturer = 'DEMO-CCTV-IP-2MP-BOX';
    if (process.env.QA_BARCODE_FOCUS !== 'purchase') {
        await page.goto(`${url}/inventory/items`);
        await page.getByRole('button', { name: 'Edit Kamera IP 2MP', exact: true }).click();
        const one = await page.getByLabel('Barcode baris 1', { exact: true }).inputValue();
        const base = one.endsWith('-UNIT') ? 1 : 2;
        const box = base === 1 ? 2 : 1;
        assert(await page.getByRole('button', { name: `Buat barcode baris ${base}`, exact: true }).isDisabled());
        await page.getByLabel(`Barcode baris ${base}`, { exact: true }).fill('');
        await page.getByRole('button', { name: `Buat barcode baris ${base}`, exact: true }).click();
        internal = await page.getByLabel(`Barcode baris ${base}`, { exact: true }).inputValue();
        assert.match(internal, /^\d{20}$/);
        manufacturer = '00123456789012';
        await page.getByLabel(`Barcode baris ${box}`, { exact: true }).fill(manufacturer);
        await page.getByLabel(`Barcode baris ${box}`, { exact: true }).press('Enter');
        assert(await page.getByRole('dialog').isVisible());
        await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
        await page.getByRole('dialog').waitFor({ state: 'detached' });
        await page.getByRole('button', { name: 'Label barcode Kamera IP 2MP', exact: true }).click();
        await page.getByRole('img', { name: `Barcode ${internal}`, exact: true }).waitFor();
        await page.getByLabel('Jumlah label', { exact: true }).fill('3');
        await page.getByLabel('Lebar label (mm)', { exact: true }).fill('40');
        assert(await page.getByRole('button', { name: 'Cetak label', exact: true }).isDisabled());
        await page.getByLabel('Lebar label (mm)', { exact: true }).fill('90');
        await shot('barcode-label-desktop');
        const svg = page.getByRole('img', { name: `Barcode ${internal}`, exact: true });
        assert(
            await svg.evaluate(async (node) => {
                const source = new XMLSerializer().serializeToString(node);
                const object = URL.createObjectURL(new Blob([source], { type: 'image/svg+xml' }));
                try {
                    const image = new Image();
                    image.src = object;
                    await image.decode();
                    const canvas = document.createElement('canvas');
                    canvas.width = 800;
                    canvas.height = 200;
                    const context = canvas.getContext('2d');
                    context.fillStyle = '#fff';
                    context.fillRect(0, 0, 800, 200);
                    context.drawImage(image, 0, 0, 800, 200);
                    const pixels = context.getImageData(0, 0, 800, 200).data;
                    let dark = 0;
                    let light = 0;
                    for (let i = 0; i < pixels.length; i += 4) {
                        if (pixels[i] < 40) dark++;
                        if (pixels[i] > 220) light++;
                    }
                    return dark > 1000 && light > 1000;
                } finally {
                    URL.revokeObjectURL(object);
                }
            }),
        );
        const labelHtml = await page.locator('[aria-label="Pratinjau label"] .barcode-label').evaluate((node) => node.outerHTML);
        const css = await readFile(path.join(root, 'resources/js/pages/inventory/items/components/barcode-label.css'), 'utf8');
        await writeFile(
            path.join(artifacts, 'label-visual.html'),
            `<!doctype html><html lang="id"><meta charset="utf-8"><title>QA label CCTV</title><style>${css}</style><body>${labelHtml}</body></html>`,
        );
        await page.keyboard.press('Control+Enter');
        assert.equal(await page.evaluate(() => window.__prints), 1);
        await page.emulateMedia({ media: 'print' });
        assert(await page.locator('.barcode-print').isVisible());
        assert(!(await page.locator('#app').isVisible()));
        assert.equal(await page.locator('.barcode-print-cell').count(), 3);
        await shot('barcode-a4-print');
        await page.emulateMedia({ media: 'screen' });
        await page.setViewportSize({ width: 1024, height: 768 });
        await shot('barcode-label-tablet');
        await page.getByRole('button', { name: 'Tutup', exact: true }).click();
        checks.push(
            'Master preserves existing code, explicit internal generation/save, OEM leading zero, Code128 pixels, width guard and isolated A4 print PASS',
        );

        await page.goto(`${url}/pos`);
        await choose('Gudang', 'Gudang Utama');
        await choose('Pelanggan', 'Installer CCTV Demo');
        const scanner = page.getByLabel('Scan barcode', { exact: true });
        for (let i = 0; i < 3; i++) {
            await scanner.fill(internal);
            await scanner.press('Enter');
        }
        await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).filter({ visible: true }).waitFor();
        await page.waitForFunction(() => document.querySelector('[aria-label="Kuantitas Kamera IP 2MP"]')?.value === '3');
        await page.locator('[aria-label="Memproses scan"]').waitFor({ state: 'hidden' });
        await scanner.fill('UNKNOWN-BARCODE');
        await scanner.press('Enter');
        await page.getByText('Barcode atau SKU tidak tersedia untuk penjualan.', { exact: true }).waitFor();
        await scanner.fill(manufacturer);
        await scanner.press('Enter');
        await page.waitForFunction(() => document.querySelectorAll('[aria-label="Kuantitas Kamera IP 2MP"]').length === 2);
        await page.locator('[aria-label="Memproses scan"]').waitFor({ state: 'hidden' });
        await scanner.fill(manufacturer);
        await scanner.press('Enter');
        await page.getByText('Jumlah scan melebihi stok gudang.', { exact: true }).waitFor();
        assert.deepEqual(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).evaluateAll((nodes) => nodes.map((node) => node.value)), [
            '3',
            '1',
        ]);
        await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click();
        await page.getByTestId('cart-subtotal').filter({ hasText: 'Rp' }).waitFor();
        await shot('scanner-pos-tablet');
        assert.equal(verify().ledger, baseline.ledger);
        checks.push('Immediate Enter/burst x3, correct units and leading zero, unknown rejected, aggregate stock blocked, quote only/no ledger PASS');
    }

    await page.goto(`${url}/purchasing/purchase-orders`);
    await page.getByRole('button', { name: 'Tambah draft pembelian', exact: true }).click();
    const purchaseScan = page.getByLabel('Scan barcode', { exact: true });
    await purchaseScan.fill(manufacturer);
    await purchaseScan.press('Enter');
    await page.waitForFunction(() => document.querySelector('#purchase-unit-0')?.textContent?.includes('Dus'));
    await page.getByLabel('Kuantitas 1', { exact: true }).fill('2');
    await page.getByLabel('Harga beli 1', { exact: true }).fill('100.000');
    await purchaseScan.fill(manufacturer);
    await purchaseScan.press('Enter');
    await page.waitForFunction(() => document.querySelector('#purchase-quantity-0')?.value === '3');
    assert.equal(await page.getByLabel('Kuantitas 1', { exact: true }).inputValue(), '3');
    assert.equal(await page.getByLabel('Harga beli 1', { exact: true }).inputValue(), '100.000');
    await purchaseScan.fill(internal);
    await purchaseScan.press('Enter');
    await page.getByText('Barang sudah memakai satuan berbeda. Periksa satuan dan harga beli pada baris tersebut.', { exact: true }).waitFor();
    assert.equal(await page.getByLabel('Kuantitas 1', { exact: true }).inputValue(), '3');
    assert.equal(verify().orders.length, 0);
    await choose('Supplier', 'DEMO-SUP-CCTV - Distribusi CCTV Demo');
    await choose('Gudang', 'WH-MAIN - Gudang Utama');
    await shot('scanner-purchase');
    await page.getByRole('button', { name: 'Simpan draft', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    const result = verify();
    assert.equal(result.orders.length, 1);
    assert.equal(result.orders[0].status, 'draft');
    assert.equal(Number(result.lines[0].quantity), 3);
    assert.equal(Number(result.lines[0].unit_cost), 100000);
    assert.equal(result.ledger, baseline.ledger);
    checks.push('Purchase scanner Enter does not submit; same unit increments, cost retained, other unit rejected; draft saved without ledger PASS');
    assert.deepEqual(errors, []);
    const report = { status: 'PASS', focus: process.env.QA_BARCODE_FOCUS === 'purchase' ? 'purchase' : 'full', checks, errors, artifacts };
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
