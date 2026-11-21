import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { FakeBarcodeInput } from '../../tests/Browser/Hardware/fake-barcode-input.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
assert(process.env.QA_PLAYWRIGHT_MODULE, 'Gunakan instalasi Playwright existing; jangan install package.');
const { chromium } = createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
const qa = path.join(root, 'storage/framework/testing', `hardware-scanner-${randomBytes(8).toString('hex')}`);
for (const folder of ['', 'framework/views', 'framework/cache/data', 'logs']) await mkdir(path.join(qa, folder), { recursive: true });
await writeFile(path.join(qa, 'database.sqlite'), '');
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
    ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`, POS_HARDWARE_ENABLED: 'true',
    APP_CONFIG_CACHE: path.join(qa, 'config.php'), APP_ROUTES_CACHE: path.join(qa, 'routes.php'),
    LARAVEL_STORAGE_PATH: qa, DB_CONNECTION: 'sqlite', DB_DATABASE: path.join(qa, 'database.sqlite'),
    CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `hardware_qa_${randomBytes(6).toString('hex')}`,
    SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync', BCRYPT_ROUNDS: '4',
};
const password = randomBytes(32).toString('hex');
const fixture = (mode) => {
    const result = spawnSync('php', ['tests/Browser/Hardware/scanner-fixture.php', mode], {
        cwd: root, env, input: mode === 'setup' ? JSON.stringify({ password }) : '', encoding: 'utf8', windowsHide: true,
    });
    assert.equal(result.status, 0, result.stderr || 'Fixture gagal; periksa log QA terisolasi.');
    return JSON.parse(result.stdout);
};
const before = fixture('setup');
await writeFile(path.join(qa, 'before.json'), JSON.stringify(before, null, 2));
const bootCheck = fixture('verify');
assert.deepEqual(bootCheck.table_fingerprints, before.table_fingerprints, 'Snapshot setup dan bootstrap baru harus identik.');
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true,
});
const report = { status: 'RUNNING', software: 'NOT_RUN', physical: 'PHYSICAL_PENDING', artifacts: qa, checks: [], errors: [], expectedHttpErrors: 0 };
let browser;
let browserContext;
let page;
const pattern = '**/platform/hardware/scanner/resolve?*';
const endpoint = '/platform/hardware/scanner';
const check = (name) => report.checks.push({ name, result: 'PASS' });
const waitRows = async (page, count) => {
    await page.waitForFunction((value) => document.querySelectorAll('[data-testid="scan-result"]').length === value, count);
    await page.locator('[data-testid="scan-pending"]').filter({ hasText: /^0$/ }).waitFor();
};
try {
    for (let i = 0; i < 60; i++) {
        try {
            if ((await fetch(url + '/login', { signal: AbortSignal.timeout(2000) })).ok) break;
        } catch { /* Wait for the disposable server. */ }
        if (i === 59) throw new Error('Server QA tidak siap.');
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    console.log(JSON.stringify({ event: 'QA_READY', url, artifacts: qa }));
    browser = await chromium.launch({ channel: 'chrome', headless: true });
    browserContext = await browser.newContext({ viewport: { width: 1366, height: 768 } });
    await browserContext.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await browserContext.newPage();
    page.on('pageerror', (error) => report.errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() !== 'error') return;
        if (/Failed to load resource:.*(?:422|403|404|429|ERR_FAILED)/.test(message.text())) report.expectedHttpErrors++;
        else report.errors.push(message.text());
    });
    const login = async (target, email) => {
        await target.goto(url + '/login');
        await target.locator('#email').fill(email);
        await target.locator('#password').fill(password);
        await target.locator('button[type="submit"]').click();
        await target.waitForURL('**/dashboard');
    };
    await login(page, 'scanner.operator@example.test');
    await page.goto(url + endpoint);
    await page.getByRole('heading', { name: 'Uji barcode satuan jual', exact: true }).waitFor();
    const scanner = new FakeBarcodeInput(page);
    const clear = async () => {
        await page.getByRole('button', { name: 'Bersihkan hasil', exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Bersihkan', exact: true }).click();
        await page.getByRole('dialog').waitFor({ state: 'hidden' });
        await waitRows(page, 0);
    };
    await page.keyboard.press('Alt+s');
    assert(await page.locator('#hardware-scan-code').evaluate((input) => input === document.activeElement));
    await scanner.emit('0012345', { enter: false });
    await page.waitForTimeout(150);
    assert.equal(await page.locator('[data-testid="scan-result"]').count(), 0);
    await page.keyboard.press('Enter');
    await waitRows(page, 1);
    assert((await page.locator('[data-testid="scan-result"]').first().innerText()).includes('0012345'));
    await scanner.emit('0012345');
    await waitRows(page, 2);
    await scanner.emit('DEMO-CCTV-2MP-BOX');
    await waitRows(page, 3);
    assert((await page.locator('[data-testid="scan-result"]').first().innerText()).includes('Dus'));
    check('Keyboard/Enter, leading zero, deliberate duplicate and exact unit');
    for (const code of ['UNKNOWN', 'PURCHASE-ONLY']) {
        await scanner.emit(code);
        await page.getByRole('cell', { name: code, exact: true }).waitFor();
    }
    await waitRows(page, 5);
    assert.equal(await page.getByText('Belum berhasil', { exact: true }).count(), 2);
    check('Unknown and purchase-only denied without mutation');
    await clear();

    let release;
    let requested = 0;
    const latch = new Promise((resolve) => { release = resolve; });
    await page.route(pattern, async (route) => { requested++; await latch; await route.continue().catch(() => {}); });
    for (let i = 0; i < 21; i++) await scanner.emit('0012345');
    await page.getByText('Antrean uji penuh (20). Tunggu hasil sebelum scan berikutnya.', { exact: true }).waitFor();
    assert.equal(requested, 1);
    await clear();
    release();
    await page.unroute(pattern);
    await scanner.emit('DEMO-MCB-UNIT');
    await waitRows(page, 1);
    await page.waitForTimeout(150);
    assert.equal(await page.locator('[data-testid="scan-result"]').count(), 1);
    check('Bounded serial queue and clear abort/stale-response isolation');

    await page.keyboard.press('F1');
    await page.getByRole('heading', { name: 'Panduan uji scanner virtual', exact: true }).waitFor();
    const inputBefore = await page.locator('#hardware-scan-code').inputValue();
    await page.keyboard.press('Alt+s');
    assert.notEqual(await page.evaluate(() => document.activeElement?.id), 'hardware-scan-code');
    assert.equal(await page.locator('#hardware-scan-code').inputValue(), inputBefore);
    await page.keyboard.press('Escape');
    await page.getByRole('dialog').waitFor({ state: 'hidden' });
    check('PageGuide F1/Sheet/Escape and dialog focus protection');

    await clear();
    await page.route(pattern, (route) => route.abort('failed'), { times: 1 });
    await scanner.emit('0012345');
    await waitRows(page, 1);
    assert.equal(await page.getByText('Belum berhasil', { exact: true }).count(), 1);
    check('Disconnected transport simulation produces one error, no retry');
    await clear();
    let timeoutCalls = 0;
    await page.route(pattern, async (route) => {
        timeoutCalls++;
        await new Promise((resolve) => setTimeout(resolve, 16000));
        await route.continue().catch(() => {});
    });
    await scanner.emit('0012345');
    await waitRows(page, 1);
    assert((await page.locator('[data-testid="scan-result"]').innerText()).includes('habis waktu'));
    assert.equal(timeoutCalls, 1);
    await page.unroute(pattern);
    check('Timeout after15s is reported without automatic resubmit');
    await clear();

    await page.route(pattern, async (route) => {
        await new Promise((resolve) => setTimeout(resolve, 400));
        await route.continue().catch(() => {});
    }, { times: 1 });
    await scanner.emit('0012345');
    await page.goto(url + '/dashboard');
    await page.waitForTimeout(600);
    await page.goto(url + endpoint);
    assert.equal(await page.locator('[data-testid="scan-result"]').count(), 0);
    check('Unmount cancels request and does not persist diagnostics');

    await scanner.emit('0012345');
    await scanner.emit('DEMO-MCB-UNIT');
    await waitRows(page, 2);
    for (const [name, width, height, dark] of [['desktop',1366,768,false],['tablet-landscape',1024,768,false],['tablet-portrait-dark',768,1024,true]]) {
        await page.setViewportSize({ width, height });
        await page.getByRole('button', { name: 'Tema', exact: true }).click();
        await page.getByRole('menuitem', { name: dark ? 'Dark' : 'Light', exact: true }).click();
        await page.waitForTimeout(400);
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        await page.screenshot({ path: path.join(qa, `${name}.png`), fullPage: true });
        check(`Layout/overflow ${name}`);
    }
    const deniedContext = await browser.newContext();
    await deniedContext.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    const denied = await deniedContext.newPage();
    await login(denied, 'scanner.denied@example.test');
    assert.equal((await deniedContext.request.get(url + endpoint + '/resolve?code=0012345')).status(), 403);
    await deniedContext.close();
    check('Unauthorized user denied at backend');
    const after = fixture('verify');
    await writeFile(path.join(qa, 'after.json'), JSON.stringify(after, null, 2));
    const changedTables = Object.keys(before.table_fingerprints).filter((name) => before.table_fingerprints[name] !== after.table_fingerprints[name]);
    assert.deepEqual(changedTables, [], 'Ada tabel berubah selama QA: ' + changedTables.join(', '));
    assert.equal(after.fingerprint, before.fingerprint);
    check(`Business/master/grant/audit fingerprint identical (${before.table_count} tables)`);
    assert.deepEqual(report.errors, []);
    report.status = 'PASS';
    report.software = 'VIRTUAL_PASSED (scanner slice only)';
    console.log(JSON.stringify({ status: report.status, checks: report.checks.length, artifacts: qa, software: report.software, physical: report.physical }));
    const reviewMs = Math.min(180000, Math.max(0, Number(process.env.QA_HARDWARE_REVIEW_MS) || 0));
    if (reviewMs) {
        console.log(JSON.stringify({ event: 'DEVTOOLS_REVIEW_WINDOW', url, milliseconds: reviewMs }));
        await new Promise((resolve) => setTimeout(resolve, reviewMs));
    }
} catch (error) {
    report.status = 'FAIL';
    report.errors.push(error.message);
    if (page && !page.isClosed()) await page.screenshot({ path: path.join(qa, 'failure.png'), fullPage: true }).catch(() => {});
    console.error(error.message);
    process.exitCode = 1;
} finally {
    await browserContext?.close();
    await browser?.close();
    const closed = new Promise((resolve) => server.once('exit', resolve));
    if (server.exitCode === null) server.kill();
    await Promise.race([closed, new Promise((resolve) => setTimeout(resolve, 5000))]);
    report.serverStopped = server.exitCode !== null || server.signalCode !== null;
    await writeFile(path.join(qa, 'report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ event: 'QA_FINISHED', status: report.status, serverStopped: report.serverStopped, artifacts: qa }));
}
