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
const artifacts = path.join(root, 'storage/framework/testing', `item-image-browser-${id}`);
const database = path.join(root, 'storage/framework/testing', `parties-browser-${id}.sqlite`);
await mkdir(path.join(artifacts, 'framework/views'), { recursive: true });
await mkdir(path.join(artifacts, 'framework/cache'), { recursive: true });
await writeFile(database, '');
const port = await new Promise((resolve, reject) => {
    const socket = createServer(); socket.once('error', reject);
    socket.listen(0, '127.0.0.1', () => { const port = socket.address().port; socket.close(() => resolve(port)); });
});
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`, APP_CONFIG_CACHE: path.join(artifacts, 'config.php'),
    APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'), LARAVEL_STORAGE_PATH: artifacts,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database',
    SESSION_COOKIE: `image_qa_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
const password = randomBytes(32).toString('hex');
const fixture = spawnSync('php', ['tests/Browser/parties-fixture.php'], { cwd: root, env,
    input: JSON.stringify({ password, inventory_demo: true }), encoding: 'utf8', windowsHide: true });
assert.equal(fixture.status, 0, fixture.stderr);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
    { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const checks = [];
const errors = [];
let browser;
let page;
try {
    for (let i = 0; i < 60; i++) {
        try { if ((await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) })).ok) break; } catch { /* Await disposable server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage();
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (message) => { if (message.type() === 'error' && !/status of (422|404|403)/.test(message.text())) errors.push(message.text()); });
    const login = async (target, role) => {
        await target.goto(`${url}/login`);
        await target.getByLabel('Email address').fill(`qa-${role}@example.test`);
        await target.getByLabel('Password', { exact: true }).fill(password);
        await target.getByRole('button', { name: 'Log in', exact: true }).click();
        await target.waitForURL('**/dashboard');
    };
    const edit = async () => {
        await page.getByRole('button', { name: 'Edit Kamera IP 2MP', exact: true }).click();
        await page.getByLabel('Gambar utama', { exact: true }).waitFor();
    };
    const save = async () => {
        await page.getByRole('dialog').getByRole('button', { name: 'Simpan', exact: true }).click();
        await page.getByRole('dialog').waitFor({ state: 'hidden' });
        await page.locator('section[aria-busy="false"]').waitFor();
    };
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: overflow`);
        const dialog = page.getByRole('dialog');
        if (await dialog.isVisible()) {
            const bounds = await dialog.boundingBox(); const size = page.viewportSize();
            assert(bounds && bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= size.width + 1 && bounds.y + bounds.height <= size.height + 1, `${name}: dialog framing`);
        }
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
    };
    const imageFixture = spawnSync('php', ['-r', '$image=imagecreatetruecolor(160,120); imagefill($image,0,0,imagecolorallocate($image,215,234,240)); imagefilledrectangle($image,30,25,130,95,imagecolorallocate($image,40,110,120)); imagepng($image);'], { cwd: root, windowsHide: true });
    assert.equal(imageFixture.status, 0, 'PNG fixture gagal.');
    const png = imageFixture.stdout;
    const upload = () => page.getByLabel('Gambar utama', { exact: true }).setInputFiles({ name: 'camera.png', mimeType: 'image/png', buffer: png });
    const cameraImage = () => page.locator('tbody tr').filter({ hasText: 'Kamera IP 2MP' }).getByRole('img', { name: 'Kamera IP 2MP', exact: true });
    await login(page, 'manager');
    await page.goto(`${url}/inventory/items`);
    await edit();
    await page.getByLabel('Gambar utama', { exact: true }).setInputFiles({ name: 'invalid.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg/>') });
    await page.getByRole('dialog').getByText('Pilih JPG, PNG atau WebP maksimal 2 MB.', { exact: true }).waitFor();
    await upload();
    await shot('image-form-desktop');
    await save();
    await cameraImage().waitFor();
    await cameraImage().evaluate((image) => image.decode());
    assert(await cameraImage().evaluate((image) => image.complete && image.naturalWidth > 0));
    const firstUrl = await cameraImage().getAttribute('src');
    await page.reload(); await cameraImage().waitFor();
    await shot('image-master-desktop');
    checks.push('Invalid upload, preview, multipart update, persistence/reload and private image PASS');
    await edit(); await page.getByRole('button', { name: 'Hapus gambar', exact: true }).click();
    await page.getByRole('button', { name: 'Batalkan perubahan gambar', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Batal', exact: true }).click();
    assert.equal(await cameraImage().getAttribute('src'), firstUrl);
    await page.setViewportSize({ width: 1024, height: 768 });
    await edit(); await upload(); await shot('image-form-tablet'); await save();
    const secondUrl = await cameraImage().getAttribute('src');
    assert.notEqual(firstUrl, secondUrl);
    assert.equal((await page.request.get(firstUrl)).status(), 404);
    checks.push('Cancel/remove undo, tablet replace and old URL gone PASS');
    const cashierContext = await browser.newContext({ viewport: { width: 1024, height: 768 } });
    await cashierContext.route('https://fonts.bunny.net/**', (r) => r.fulfill({ contentType: 'text/css', body: '' }));
    const cashier = await cashierContext.newPage();
    await login(cashier, 'cashier');
    assert.equal((await cashier.request.get(secondUrl)).status(), 200);
    await cashier.goto(`${url}/pos`);
    for (const [label, name] of [['Gudang', 'Gudang Utama'], ['Pelanggan', 'Installer CCTV Demo']]) {
        await cashier.getByLabel(label, { exact: true }).click();
        await cashier.getByRole('option', { name, exact: true }).click();
    }
    await cashier.getByLabel('Cari barang', { exact: true }).fill('DEMO-CCTV-IP-2MP-BOX');
    await cashier.locator('section[aria-label="Katalog barang"][aria-busy="false"]').waitFor();
    const posImage = cashier.getByRole('article').filter({ hasText: 'Kamera IP 2MP' }).getByRole('img', { name: 'Kamera IP 2MP', exact: true });
    await posImage.waitFor();
    await posImage.evaluate((image) => image.decode());
    assert.equal(await posImage.getAttribute('src'), secondUrl);
    await cashier.getByLabel('Cari barang', { exact: true }).press('Enter');
    await cashier.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).waitFor();
    await cashier.getByRole('button', { name: 'Periksa keranjang', exact: true }).click();
    await cashier.getByTestId('cart-subtotal').filter({ hasText: 'Rp 2.470.000' }).waitFor();
    assert(await cashier.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await cashier.screenshot({ path: path.join(artifacts, 'image-pos-tablet.png'), fullPage: true });
    checks.push('Cashier image permission, POS image decode, barcode BOX and Installer price quote PASS');
    await page.goto(`${url}/inventory/items`); await edit();
    await page.getByRole('button', { name: 'Hapus gambar', exact: true }).click(); await save();
    assert.equal(await cameraImage().count(), 0);
    await page.reload();
    await page.getByRole('button', { name: 'Edit Kamera IP 2MP', exact: true }).waitFor();
    assert.equal(await cameraImage().count(), 0);
    checks.push('Explicit remove persisted with no image placeholder PASS');
    assert.deepEqual(errors, []);
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ checks, errors }, null, 2));
    console.log(JSON.stringify({ status: 'PASS', checks, artifacts }, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'report.json'), JSON.stringify({ status: 'FAIL', checks, errors }, null, 2));
    throw error;
} finally {
    if (browser) await browser.close();
    server.kill();
    await new Promise((resolve) => { if (server.exitCode !== null) resolve(); else server.once('exit', resolve); });
}
