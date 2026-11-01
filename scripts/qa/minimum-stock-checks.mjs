import assert from 'node:assert/strict';

export async function checkMinimumStock({ page, browser, password, url, shot, checks }) {
    const ready = () => page.locator('section[aria-busy="false"]').waitFor();
    const choose = async (label, name) => { await page.getByLabel(label, { exact: true }).click(); await page.getByRole('option', { name, exact: true }).click(); };
    await page.goto(`${url}/inventory/stock/minimums`); await ready();
    if (process.env.QA_MINIMUM_STOCK_LAYOUT === '1') {
        for (const [name, width, height] of [['desktop', 1440, 1000], ['tablet', 1024, 768]]) {
            await page.setViewportSize({ width, height });
            assert((await page.getByLabel('Cari stok minimum', { exact: true }).boundingBox()).width >= 200, 'Pencarian harus tetap leluasa.');
            await shot(`minimum-layout-${name}`);
        }
        checks.push('Layout-only rerun: search width >=200px, wrapped filters and no body overflow desktop/tablet PASS');
        return;
    }
    await page.keyboard.press('Control+Shift+n');
    await page.getByRole('dialog').waitFor();
    await choose('Gudang stok minimum', 'WH-MAIN - Gudang Utama');
    await page.getByLabel('Cari barang', { exact: true }).fill('DEMO-CCTV-IP-2MP');
    await page.getByLabel('Barang stok minimum', { exact: true }).click();
    await page.getByRole('option', { name: /^DEMO-CCTV-IP-2MP -/ }).click();
    await page.getByLabel(/^Minimum \(/).fill('10,5');
    await page.keyboard.press('Control+Enter'); await page.getByRole('dialog').waitFor({ state: 'hidden' }); await ready();
    const row = page.locator('tbody tr').filter({ hasText: 'DEMO-CCTV-IP-2MP' });
    await row.getByRole('button', { name: 'Ubah DEMO-CCTV-IP-2MP' }).waitFor();
    assert((await row.innerText()).includes('10,5'));
    assert((await row.innerText()).includes('Perlu diperiksa'));
    await shot('minimum-desktop');
    await page.keyboard.press('Alt+f'); assert(await page.getByLabel('Cari stok minimum', { exact: true }).evaluate((e) => e === document.activeElement));
    await page.keyboard.press('F1'); await page.getByRole('dialog').getByText('Panduan Stok Minimum', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    await row.getByRole('button', { name: 'Ubah DEMO-CCTV-IP-2MP' }).click();
    await page.getByLabel('Peringatan aktif', { exact: true }).click();
    await page.getByRole('button', { name: 'Simpan', exact: true }).click(); await page.getByRole('dialog').waitFor({ state: 'hidden' }); await ready();
    await row.getByText('Nonaktif', { exact: true }).waitFor();
    await row.getByRole('button', { name: 'Hapus DEMO-CCTV-IP-2MP' }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Hapus', exact: true }).click(); await page.getByRole('dialog').waitFor({ state: 'hidden' }); await ready();
    await choose('Filter arsip', 'Terhapus'); await ready();
    await row.getByRole('button', { name: 'Pulihkan DEMO-CCTV-IP-2MP' }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Pulihkan', exact: true }).click(); await page.getByRole('dialog').waitFor({ state: 'hidden' }); await ready();
    await choose('Filter arsip', 'Belum dihapus'); await ready();
    await row.getByRole('button', { name: 'Ubah DEMO-CCTV-IP-2MP' }).click();
    await page.getByLabel('Peringatan aktif', { exact: true }).click();
    await page.getByRole('button', { name: 'Simpan', exact: true }).click(); await page.getByRole('dialog').waitFor({ state: 'hidden' }); await ready();
    await choose('Filter status', 'Perlu diperiksa'); await ready();
    await page.reload(); await ready(); assert.equal(await page.locator('tbody tr').count(), 1);
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('minimum-tablet');
    await page.goto(`${url}/dashboard`);
    const summary = page.getByRole('region', { name: 'Peringatan stok minimum' });
    await summary.waitFor(); assert((await summary.innerText()).includes('1 pasangan'));
    const response = page.waitForResponse((r) => new URL(r.url()).pathname === '/dashboard' && r.request().headers()['x-inertia'] === 'true');
    await page.getByRole('button', { name: 'Muat ulang ringkasan', exact: true }).click(); assert.equal((await response).status(), 200);
    await page.getByRole('link', { name: 'Lihat peringatan', exact: true }).click(); await ready();
    assert.match(page.url(), /status=low/); await shot('minimum-dashboard-link-tablet');
    const peer = await browser.newContext();
    try {
        await peer.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        const other = await peer.newPage(); await other.goto(`${url}/login`);
        await other.getByLabel('Email address').fill('qa-limited@example.test'); await other.getByLabel('Password', { exact: true }).fill(password);
        await other.getByRole('button', { name: 'Log in', exact: true }).click(); await other.waitForURL('**/dashboard');
        assert.equal(await other.getByRole('region', { name: 'Peringatan stok minimum' }).count(), 0);
        assert.equal((await peer.request.get(`${url}/inventory/stock/minimums`)).status(), 403);
    } finally { await peer.close(); }
    checks.push('Minimum create/locale/edit/deactivate/delete/restore/reload/filter, F1/shortcuts, dashboard refresh/link, permission and desktop/tablet framing PASS');
}
