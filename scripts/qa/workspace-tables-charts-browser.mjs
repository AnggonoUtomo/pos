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
const database = `${artifacts}.sqlite`;
await mkdir(path.join(artifacts, 'views'), { recursive: true }); await writeFile(database, '');
const port = await new Promise((resolve, reject) => { const server = createServer(); server.once('error', reject); server.listen(0, '127.0.0.1', () => { const port = server.address().port; server.close(() => resolve(port)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    APP_CONFIG_CACHE: path.join(artifacts, 'config.php'), APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'), VIEW_COMPILED_PATH: path.join(artifacts, 'views'),
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `visual_qa_${id}`,
    SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
const fixture = (input) => {
    const result = spawnSync('php', ['tests/Browser/mvp-checkout-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
    assert.equal(result.status, 0, result.stderr); return JSON.parse(result.stdout);
};
const password = randomBytes(32).toString('hex');
const setup = fixture({ password, dashboard_only: true, pagination_demo: process.env.QA_GLOBAL_PAGINATION === '1' || process.env.QA_SEARCH_SURFACES === '1', audit_demo: process.env.QA_AUDIT_LOG === '1' });
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const checks = []; const errors = []; let browser; let page;
const baseline = process.env.QA_VISUAL_BASELINE === '1';
const reportOnly = process.env.QA_VISUAL_REPORT_ONLY === '1';
try {
    for (let i = 0; i < 80; i++) { try { const response = await fetch(`${url}/login`, { signal: AbortSignal.timeout(3000) }); await response.text(); if (response.ok) break; } catch { /* Wait for isolated server. */ } await new Promise((resolve) => setTimeout(resolve, 250)); }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
    page = await context.newPage(); page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
    await page.goto(`${url}/login`); await page.getByLabel('Email address').fill('qa-manager@example.test'); await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click(); await page.waitForURL('**/dashboard');
    const shot = async (name) => {
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name}: body overflow`);
        await page.screenshot({ path: path.join(artifacts, `${name}.png`), fullPage: true, animations: 'disabled' });
    };
    if (process.env.QA_AUDIT_LOG === '1') {
        if (process.env.QA_DEVTOOLS_ENTRY) {
            const { auditDevtoolsSmoke } = await import('./audit-log-devtools-smoke.mjs');
            checks.push(await auditDevtoolsSmoke({ url, password, artifacts, entry: process.env.QA_DEVTOOLS_ENTRY }));
        }
        const { checkAuditLog } = await import('./audit-log-checks.mjs');
        await checkAuditLog({ page, browser, password, url, shot, checks, errors });
    } else if (process.env.QA_SEARCH_SURFACES === '1') {
        const { checkSearchSurfaces } = await import('./search-filter-checks.mjs');
        await checkSearchSurfaces({ page, url, shot, checks });
    } else if (process.env.QA_GLOBAL_PAGINATION === '1') {
        const { checkGlobalPagination } = await import('./global-pagination-checks.mjs');
        await checkGlobalPagination({ page, url, shot, checks });
    } else if (process.env.QA_REPORT_PROFILE === '1') {
        const { checkReportProfile } = await import('./report-profile-checks.mjs');
        await checkReportProfile({ page, url, setup, shot, checks });
    } else {
    for (const [route, name] of (reportOnly ? [['/reports/operations', 'reports']] : [['/inventory/units', 'units'], ['/sales/invoices', 'invoices'], ['/reports/operations', 'reports'], ['/dashboard', 'dashboard']])) {
        await page.goto(`${url}${route}`); await page.locator('h1').waitFor();
        if (!baseline) {
            await page.locator('table.workspace-table').waitFor();
            const styles = await page.locator('table.workspace-table').first().evaluate((table) => ({ head: getComputedStyle(table.querySelector('thead')).backgroundColor, body: getComputedStyle(table).backgroundColor }));
            assert.notEqual(styles.head, styles.body, `${name}: header contrast`);
        }
        await shot(`${baseline ? 'before' : 'after'}-${name}-desktop`);
    }
    if (!baseline && reportOnly) {
        await page.getByTestId('comparison-chart').waitFor(); await page.getByTestId('flow-chart').waitFor();
        assert.equal(await page.getByRole('heading', { name: 'Arus pembayaran seluruh gudang', exact: true }).count(), 0);
        assert.match(await page.getByTestId('comparison-chart').innerText(), /s\.d\./);
        await page.setViewportSize({ width: 1024, height: 768 }); await page.emulateMedia({ reducedMotion: 'reduce' });
        await shot('reports-final-tablet');
        const transition = await page.locator('tbody tr').first().evaluate((row) => getComputedStyle(row).transitionDuration); assert.equal(transition, '0s');
        await page.goto(`${url}/dashboard`); await page.getByTestId('comparison-chart').waitFor();
        const before = await page.getByTestId('comparison-chart').innerText();
        const response = page.waitForResponse((response) => new URL(response.url()).pathname === '/dashboard' && response.request().headers()['x-inertia'] === 'true');
        await page.getByRole('button', { name: 'Muat ulang ringkasan', exact: true }).click(); assert.equal((await response).status(), 200);
        await page.waitForFunction(() => document.querySelector('[data-testid="comparison-chart"]') && !document.querySelector('[aria-busy="true"]'));
        assert.equal(await page.getByTestId('comparison-chart').innerText(), before);
        checks.push('Final report layout retains payment figures without duplicate section; Indonesian period; reduced-motion table PASS');
        checks.push('Dashboard refresh preserves real chart values and returns to ready state PASS');
    }
    if (!baseline && !reportOnly) {
        await page.getByTestId('comparison-chart').waitFor(); await page.getByTestId('flow-chart').waitFor();
        assert.equal(await page.locator('[data-chart-bar]').count(), 4); assert.equal(await page.locator('[data-chart-arc]').count(), 2);
        const amount = new Intl.NumberFormat('id-ID').format(BigInt(setup.report.sales.total.split('.')[0]));
        assert((await page.getByTestId('comparison-chart').innerText()).includes(amount));
        const row = page.getByRole('button', { name: /^Penjualan lunas: Rp/ }); await row.focus(); await page.getByRole('tooltip').waitFor();
        await page.keyboard.press('Escape');
        const animations = await page.locator('[data-chart-bar]').evaluateAll((bars) => bars.map((bar) => ({ iterations: getComputedStyle(bar).animationIterationCount, name: getComputedStyle(bar).animationName })));
        assert(animations.every((value) => value.iterations === '1' && value.name === 'chart-bar-enter'));
        checks.push('Table header/surface and Units pattern; real chart values, keyboard tooltip and finite animation PASS');
        await page.setViewportSize({ width: 1024, height: 768 });
        await page.emulateMedia({ reducedMotion: 'reduce' });
        const reduced = await page.locator('[data-chart-bar]').first().evaluate((bar) => getComputedStyle(bar).animationName); assert.equal(reduced, 'none');
        await shot('dashboard-tablet-reduced-motion');
        // Local theme preference is non-sensitive; use the existing preference contract in an isolated context.
        await page.addInitScript(() => { localStorage.setItem('appearance', 'dark'); });
        await page.reload(); await page.getByTestId('comparison-chart').waitFor();
        await shot('dashboard-dark-tablet');
        await page.goto(`${url}/inventory/units`); await page.locator('table.workspace-table').waitFor();
        const beforeHover = await page.locator('tbody tr').first().evaluate((row) => getComputedStyle(row).backgroundColor);
        await page.locator('tbody tr').first().hover();
        await page.waitForTimeout(180);
        const afterHover = await page.locator('tbody tr').first().evaluate((row) => getComputedStyle(row).backgroundColor); assert.notEqual(beforeHover, afterHover);
        await shot('units-dark-tablet');
        await page.goto(`${url}/reports/operations`); await page.getByTestId('comparison-chart').waitFor();
        await shot('reports-dark-tablet');
        await page.getByLabel('Dari tanggal', { exact: true }).fill('2000-01-01'); await page.getByLabel('Sampai tanggal', { exact: true }).fill('2000-01-01');
        await page.getByRole('button', { name: 'Terapkan', exact: true }).click(); await page.getByText('Belum ada nilai transaksi pada periode ini.', { exact: true }).waitFor();
        assert.equal(await page.locator('[data-chart-arc]').count(), 0); await shot('reports-zero-tablet');
        checks.push('Desktop/tablet light/dark, hover, reduced motion and zero-period filter PASS');
    }
    }
    assert.deepEqual(errors, []); assert.equal(fixture({ verify_dashboard: true, fingerprint: setup.fingerprint, audit_fingerprint: setup.audit_fingerprint }).status, 'PASS');
    const report = { status: 'PASS', baseline, checks, errors, artifacts }; await writeFile(path.join(artifacts, 'visual-report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} catch (error) {
    if (page) await page.screenshot({ path: path.join(artifacts, 'failure.png'), fullPage: true }).catch(() => {});
    await writeFile(path.join(artifacts, 'visual-report.json'), JSON.stringify({ status: 'FAIL', checks, errors, message: error.message }, null, 2)); throw error;
} finally { if (browser) await browser.close(); server.kill(); await new Promise((resolve) => { if (server.exitCode !== null) resolve(); else server.once('exit', resolve); }); }
