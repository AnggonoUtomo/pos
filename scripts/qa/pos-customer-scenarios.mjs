import assert from 'node:assert/strict';
import path from 'node:path';

export async function checkCustomerAutocomplete(page, artifacts, checks) {
    const customer = page.getByRole('combobox', { name: 'Pelanggan', exact: true });
    assert.equal(await customer.inputValue(), 'Pelanggan Umum');
    await page.getByRole('button', { name: 'Tambah Kamera IP 2MP', exact: true }).click();
    await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click();
    await page.getByText('Terperiksa', { exact: true }).waitFor();
    const oldTotal = await page.getByTestId('cart-total').innerText();
    for (const [view, width, height] of [
        ['Visual', 1440, 900],
        ['Klasik', 1024, 768],
    ]) {
        await page.setViewportSize({ width, height });
        await page.getByRole('radio', { name: `Tampilan ${view}`, exact: true }).click();
        await customer.fill('  demo-inst  ');
        await page.getByRole('option', { name: 'Installer CCTV Demo', exact: true }).waitFor();
        assert.equal(await page.getByRole('option').count(), 1);
        assert.match(await page.getByRole('listbox').innerText(), /DEMO-INSTALLER/);
        await page.screenshot({ path: path.join(artifacts, `customer-${view.toLowerCase()}.png`), fullPage: true });
        const box = await page.getByRole('listbox').boundingBox();
        assert(box && box.x >= 0 && box.y >= 0 && box.x + box.width <= width + 1 && box.y + box.height <= height + 1);
        await customer.press('Escape');
        assert.equal(await customer.inputValue(), 'Pelanggan Umum');
        assert.equal(await page.getByTestId('cart-total').innerText(), oldTotal);
        await customer.fill('tidak-ada-pelanggan-ini');
        await page.getByText('Pelanggan tidak ditemukan.', { exact: true }).waitFor();
        assert.equal(await page.getByRole('option').count(), 0);
        await customer.press('Escape');
        assert.equal(await customer.inputValue(), 'Pelanggan Umum');
        checks.push(`${view}: code search, empty results, cancellation, quote unchanged and dropdown framing PASS`);
    }
    await customer.fill('installer cctv');
    await page.getByRole('option', { name: 'Installer CCTV Demo', exact: true }).waitFor();
    await customer.press('ArrowDown');
    await customer.press('Enter');
    await page.getByRole('listbox').waitFor({ state: 'hidden' });
    assert.equal(await customer.inputValue(), 'Installer CCTV Demo');
    assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '1');
    assert.equal(await page.getByTestId('cart-total').count(), 0, 'Old quote must be invalidated');
    assert(await page.getByRole('button', { name: 'Pembayaran', exact: true }).isDisabled());
    await page.getByRole('button', { name: 'Periksa keranjang', exact: true }).click();
    await page.getByText('Terperiksa', { exact: true }).waitFor();
    const response = page.waitForResponse((response) => response.url().includes('/pos/catalog') && response.status() === 200);
    await customer.fill('eceran');
    await page.getByRole('option', { name: 'Pelanggan Umum', exact: true }).click();
    const catalog = await (await response).json();
    assert.equal(catalog.customer.levelName, 'Eceran');
    assert.equal(await customer.inputValue(), 'Pelanggan Umum');
    assert.equal(await page.getByLabel('Kuantitas Kamera IP 2MP', { exact: true }).inputValue(), '1');
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    checks.push('Name/level search, keyboard and click selection, quote invalidation, current level refresh and cart preservation PASS');
}
