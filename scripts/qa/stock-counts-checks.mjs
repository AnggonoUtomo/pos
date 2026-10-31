import assert from 'node:assert/strict';

export async function checkStockCounts({ page, browser, password, url, shot, checks }) {
    const ready = () => page.locator('section[aria-busy="false"]').waitFor();
    const apply = async (name) => { await page.getByRole('button', { name, exact: true }).click(); await ready(); };
    const snapshot = async () => {
        const version = await page.evaluate(() => JSON.parse(document.querySelector('[data-page]').getAttribute('data-page')).version);
        const response = await page.request.get(page.url(), { headers: { 'X-Inertia': 'true', 'X-Inertia-Version': version } });
        assert.equal(response.status(), 200); return (await response.json()).props.record;
    };
    const scaled = (value) => { const [whole, fraction = ''] = value.split('.'); return BigInt(whole) * 1000000n + BigInt(fraction.padEnd(6, '0')); };
    const local = (value) => {
        const whole = value / 1000000n; const fraction = (value % 1000000n).toString().padStart(6, '0').replace(/0+$/, '');
        return `${whole}${fraction ? ',' + fraction : ''}`;
    };
    const begin = async () => {
        await page.goto(`${url}/inventory/stock/counts`);
        await apply('Mulai opname');
        await page.getByLabel('Gudang opname', { exact: true }).click();
        await page.getByRole('option', { name: 'WH-MAIN - Gudang Utama', exact: true }).click();
        await page.getByLabel('Catatan sesi').fill('QA opname CCTV');
        await page.getByRole('button', { name: 'Mulai dan tahan gudang', exact: true }).click();
        await page.waitForURL(/\/inventory\/stock\/counts\/[a-z0-9]{26}$/); await ready();
        await page.getByText('Mutasi stok gudang ditahan selama opname.', { exact: true }).waitFor();
        return snapshot();
    };
    const confirm = async (name, reason) => {
        await apply(name); const dialog = page.getByRole('dialog'); await dialog.waitFor();
        if (reason) await dialog.getByLabel('Alasan / hasil pemeriksaan').fill(reason);
        await dialog.getByRole('button', { name: 'Konfirmasi', exact: true }).click();
        await dialog.waitFor({ state: 'hidden' }); await ready();
    };
    for (const direction of [-1n, 1n]) {
        const record = await begin();
        assert.equal(await page.getByRole('button', { name: 'Ajukan review', exact: true }).isDisabled(), true);
        const target = record.lines.find((line) => line.sku === 'DEMO-CCTV-IP-2MP'); assert(target);
        if (direction === -1n) {
            await page.keyboard.press('Alt+f');
            assert(await page.getByLabel('Barcode atau cari barang opname').evaluate((e) => e === document.activeElement));
            await page.getByLabel('Barcode atau cari barang opname').fill(target.sku); await page.keyboard.press('Enter');
            await page.getByLabel(`Fisik ${target.sku}`, { exact: true }).waitFor();
            await page.getByLabel('Barcode atau cari barang opname').fill('');
        }
        for (const line of record.lines) {
            const value = scaled(line.system_quantity) + (line.id === target.id ? direction * 1000000n : 0n);
            assert(value >= 0n); await page.getByLabel(`Fisik ${line.sku}`, { exact: true }).fill(local(value));
        }
        await page.keyboard.press('Control+s'); await ready();
        await page.getByRole('button', { name: 'Ajukan review', exact: true }).waitFor({ state: 'visible' });
        await confirm('Ajukan review');
        if (direction === 1n) {
            await page.getByLabel(`Harga pokok ${target.sku}`, { exact: true }).fill('40.000');
            await page.setViewportSize({ width: 1024, height: 768 }); await shot('count-review-tablet');
        } else { await shot('count-review-desktop'); }
        await confirm('Posting hasil', 'QA persetujuan fisik');
        await page.getByText('Selesai', { exact: true }).waitFor();
        assert.equal(await page.getByLabel(`Fisik ${target.sku}`, { exact: true }).count(), 0);
        const posted = await snapshot(); assert.equal(posted.status, 'posted');
        await page.reload(); assert.equal((await snapshot()).status, 'posted');
    }
    const cancelled = await begin();
    await confirm('Batalkan sesi', 'QA latihan dibatalkan');
    assert.equal((await snapshot()).status, 'cancelled');
    await page.goto(`${url}/inventory/stock/counts`);
    await page.getByLabel('Filter status', { exact: true }).click();
    await page.getByRole('option', { name: 'Dibatalkan', exact: true }).click(); await ready();
    assert.equal(await page.getByRole('button', { name: 'Buka sesi', exact: true }).count(), 0); // Links retain button styling, not button semantics.
    assert.equal(await page.getByRole('link', { name: 'Buka sesi', exact: true }).count(), 1);
    await page.keyboard.press('F1'); await page.getByRole('dialog').getByText('Panduan Stock Opname', { exact: true }).waitFor();
    await page.keyboard.press('Escape');
    await shot('count-list-tablet');
    const peer = await browser.newContext();
    try {
        await peer.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        const other = await peer.newPage(); await other.goto(`${url}/login`);
        await other.getByLabel('Email address').fill('qa-limited@example.test'); await other.getByLabel('Password', { exact: true }).fill(password);
        await other.getByRole('button', { name: 'Log in', exact: true }).click(); await other.waitForURL('**/dashboard');
        assert.equal((await peer.request.get(`${url}/inventory/stock/counts`)).status(), 403);
        assert.equal((await peer.request.get(`${url}/inventory/stock/counts/${cancelled.id}`)).status(), 403);
    } finally { await peer.close(); }
    checks.push('Stock count start/freeze notice, base counts/locale, SKU scan, save/review, out FIFO/in HPP, posted reload, cancel/release, filter, F1, permissions, desktop/tablet PASS');
}
