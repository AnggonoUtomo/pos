import assert from 'node:assert/strict';

export async function checkPriceMarkup({ page, browser, password, url, setup, shot, checks }) {
    const open = async (sku = 'DEMO-CCTV-IP-2MP') => {
        await page.goto(`${url}/inventory/items?search=${sku}`); await page.locator('tbody tr').getByRole('button', { name: /^Edit / }).click();
        await page.getByRole('dialog').waitFor();
    };
    const calculate = async () => { await page.keyboard.press('Alt+g'); await page.getByRole('button', { name: 'Terapkan ke form', exact: true }).waitFor(); await page.waitForFunction(() => ![...document.querySelectorAll('button')].find((b) => b.textContent.includes('Terapkan ke form'))?.disabled); };
    await open();
    const dialog = page.getByRole('dialog'); const field = dialog.getByLabel('Harga Unit Member toko', { exact: true });
    assert.equal(await field.inputValue(), '777.777');
    await dialog.getByLabel('Markup Member toko', { exact: true }).fill('12,5'); await calculate();
    await dialog.getByText('135.000', { exact: true }).waitFor(); assert.equal(await field.inputValue(), '777.777');
    await shot('markup-preview-desktop');
    await dialog.getByLabel('Markup Member toko', { exact: true }).fill('20');
    assert(await dialog.getByRole('button', { name: 'Terapkan ke form', exact: true }).isDisabled());
    await calculate(); await dialog.getByText('144.000', { exact: true }).waitFor();
    await page.keyboard.press('Alt+a'); assert.equal(await field.inputValue(), '144.000');
    await dialog.getByRole('button', { name: 'Batal', exact: true }).click();
    await open(); assert.equal(await field.inputValue(), '777.777'); // Apply alone never persists.
    await dialog.getByLabel('Markup Member toko', { exact: true }).fill('20'); await calculate();
    await page.keyboard.press('Alt+a'); await field.fill('145.000');
    await page.setViewportSize({ width: 1024, height: 768 }); await shot('markup-manual-tablet');
    await page.keyboard.press('Control+Enter'); await dialog.waitFor({ state: 'hidden' });
    await open(); assert.equal(await field.inputValue(), '145.000');
    assert.equal(await dialog.getByLabel('Harga Dus Member toko', { exact: true }).inputValue(), '576.000');
    await dialog.getByRole('button', { name: 'Batal', exact: true }).click();
    await open('DEMO-ELEC-MCB-6A');
    await dialog.getByLabel('Markup Member toko', { exact: true }).fill('0');
    await dialog.getByRole('button', { name: 'Hitung usulan', exact: true }).click();
    await dialog.getByText('Belum ada pembelian diterima. Harga manual tetap tersedia.', { exact: true }).waitFor();
    assert(await dialog.getByRole('button', { name: 'Terapkan ke form', exact: true }).isDisabled());
    await shot('markup-no-cost-tablet'); await dialog.getByRole('button', { name: 'Batal', exact: true }).click();
    await page.keyboard.press('F1'); await page.getByRole('dialog').getByText('Menghitung usulan harga', { exact: true }).waitFor(); await page.keyboard.press('Escape');
    const peer = await browser.newContext();
    try {
        await peer.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
        const other = await peer.newPage(); await other.goto(`${url}/login`);
        await other.getByLabel('Email address').fill('qa-limited@example.test'); await other.getByLabel('Password', { exact: true }).fill(password);
        await other.getByRole('button', { name: 'Log in', exact: true }).click(); await other.waitForURL('**/dashboard');
        const token = (await peer.cookies()).find((cookie) => cookie.name === 'XSRF-TOKEN').value;
        assert.equal((await peer.request.post(`${url}/inventory/items/${setup.markup_item_id}/price-preview`, { headers: { 'X-XSRF-TOKEN': decodeURIComponent(token), Accept: 'application/json' }, data: {} })).status(), 403);
    } finally { await peer.close(); }
    checks.push('Markup per dynamic custom level, Indonesian percent/money, preview/apply/cancel without save, manual override/persist/reload, no purchase fallback, F1/shortcuts, permission and desktop/tablet PASS');
}
