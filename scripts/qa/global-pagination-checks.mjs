import assert from 'node:assert/strict';

export async function checkGlobalPagination({ page, url, shot, checks }) {
    const selectSize = async (label, value) => {
        await page.getByRole('combobox', { name: label, exact: true }).click();
        await page.getByRole('option', { name: String(value), exact: true }).click();
    };
    const ready = async (query) => {
        await page.waitForURL((target) => query(target));
        await page.locator('[aria-busy="true"]').waitFor({ state: 'hidden' });
    };
    await page.goto(`${url}/platform/operational-settings`);
    const tablist = page.getByRole('tablist', { name: 'Kategori pengaturan' });
    assert.equal(await tablist.getAttribute('aria-orientation'), 'vertical');
    await page.getByRole('tab', { name: 'Umum', exact: true }).click();
    await selectSize('Jumlah baris default', 25);
    await page.getByRole('tab', { name: 'Cetak', exact: true }).click();
    await page.getByRole('button', { name: 'Uji cetak', exact: true }).click();
    await page.getByRole('dialog').waitFor();
    await page.keyboard.press('Escape');
    await page.getByRole('dialog').waitFor({ state: 'detached' });
    await page.getByRole('tab', { name: 'Backup', exact: true }).click();
    await page.getByRole('button', { name: 'Backup sekarang', exact: true }).click();
    await page.getByRole('dialog').waitFor();
    await page.keyboard.press('Escape');
    await page.getByRole('dialog').waitFor({ state: 'detached' });
    await page.getByRole('tab', { name: 'Umum', exact: true }).click();
    assert.equal(await page.getByRole('combobox', { name: 'Jumlah baris default' }).innerText(), '25');
    const saved = page.waitForResponse((r) => r.request().method() === 'PUT' && new URL(r.url()).pathname === '/platform/operational-settings');
    await page.getByRole('button', { name: 'Simpan pengaturan', exact: true }).click();
    assert.equal((await saved).status(), 303);
    await page.getByText('Pengaturan operasional tersimpan.', { exact: true }).first().waitFor();
    await shot('global-settings-desktop');
    checks.push('Vertical tabs retain unsaved settings, print preview/manual backup confirmation, audited global save PASS');

    await page.goto(`${url}/inventory/units`);
    const pager = page.locator('[data-table-pagination]');
    const rows = page.locator('table tbody tr');
    assert.equal(await rows.count(), 25);
    assert.equal(await page.getByRole('combobox', { name: 'Baris per halaman' }).innerText(), '25');
    assert(await pager.getByRole('button', { name: 'Halaman pertama', exact: true }).isDisabled());
    await selectSize('Baris per halaman', 10);
    await ready((target) => target.searchParams.get('per_page') === '10');
    assert.equal(await rows.count(), 10);
    await pager.getByRole('button', { name: 'Halaman 3', exact: true }).click();
    await ready((target) => target.searchParams.get('page') === '3');
    assert.equal(await rows.count(), 10);
    await selectSize('Baris per halaman', 50);
    await ready((target) => target.searchParams.get('per_page') === '50' && target.searchParams.get('page') === '1');
    assert.equal(await rows.count(), 50);
    await page.getByRole('textbox', { name: 'Cari satuan', exact: true }).fill('PAGE-');
    await page.getByRole('button', { name: 'Cari', exact: true }).click();
    await ready((target) => target.searchParams.get('search') === 'PAGE-');
    assert.equal(new URL(page.url()).searchParams.get('per_page'), '50');
    await selectSize('Baris per halaman', 10);
    await ready((target) => target.searchParams.get('per_page') === '10');
    assert.equal(new URL(page.url()).searchParams.get('search'), 'PAGE-');
    assert.equal(await rows.count(), 10);
    await pager.getByRole('button', { name: 'Halaman terakhir', exact: true }).click();
    await ready((target) => target.searchParams.get('page') === '11');
    assert(await pager.getByRole('button', { name: 'Halaman berikutnya', exact: true }).isDisabled());
    await pager.getByRole('button', { name: 'Halaman sebelumnya', exact: true }).click();
    await ready((target) => target.searchParams.get('page') === '10');
    await pager.getByRole('button', { name: 'Halaman pertama', exact: true }).click();
    await ready((target) => target.searchParams.get('page') === '1');
    await page.setViewportSize({ width: 1024, height: 768 });
    await shot('pagination-units-tablet');
    checks.push('Default25, temporary10/50, reset page1, search retention, numbered/first/previous/last navigation and boundaries PASS');

    for (const route of [
        '/inventory/warehouses',
        '/inventory/items',
        '/inventory/stock',
        '/inventory/transfers',
        '/platform/identity',
        '/sales/customers',
        '/sales/customer-levels',
        '/sales/returns',
        '/sales/invoices',
        '/sales/cashier-shifts',
        '/purchasing/suppliers',
        '/purchasing/purchase-orders',
        '/purchasing/returns',
        '/finance/payment-accounts',
        '/finance/payment-methods',
        '/finance/payments/history',
        '/reports/operations',
    ]) {
        await page.goto(url + route);
        await page.getByRole('combobox', { name: 'Baris per halaman', exact: true }).waitFor();
        assert.equal(await page.getByRole('combobox', { name: 'Baris per halaman', exact: true }).innerText(), '25', route);
    }
    const summary = await page.getByRole('definition').allTextContents();
    await selectSize('Baris per halaman', 10);
    await ready((target) => target.searchParams.get('per_page') === '10');
    assert.deepEqual(await page.getByRole('definition').allTextContents(), summary);
    await page.addInitScript(() => localStorage.setItem('appearance', 'dark'));
    await page.reload();
    await shot('pagination-report-dark-tablet');
    await page.goto(`${url}/platform/operational-settings`);
    assert.equal(await page.getByRole('combobox', { name: 'Jumlah baris default' }).innerText(), '25');
    await page.getByRole('tab', { name: 'Umum', exact: true }).focus();
    await page.keyboard.press('ArrowDown');
    assert.equal(await page.getByRole('tab', { name: 'Cetak', exact: true }).getAttribute('aria-selected'), 'true');
    await page.keyboard.press('Home');
    await shot('global-settings-dark-tablet');
    checks.push('All list workspaces use global size, report totals unchanged, global setting not overwritten, dark/tablet and keyboard tabs PASS');
}
