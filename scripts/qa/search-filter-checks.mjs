import assert from 'node:assert/strict';

export async function checkSearchSurfaces({ page, url, shot, checks }) {
    const routes = ['/inventory/units', '/inventory/warehouses', '/inventory/items', '/sales/customers', '/sales/customer-levels',
        '/purchasing/suppliers', '/finance/payment-accounts', '/finance/payment-methods', '/platform/identity', '/inventory/stock',
        '/inventory/transfers', '/purchasing/purchase-orders', '/purchasing/returns', '/sales/returns', '/sales/invoices',
        '/finance/payments/history', '/finance/cashbook', '/reports/operations', '/pos'];
    const ready = async (route) => {
        await page.goto(`${url}${route}`);
        await page.locator('.workspace-filters').first().waitFor();
        await page.waitForFunction(() => !document.querySelector('section[aria-busy="true"]'));
    };
    const framing = async () => {
        const state = await page.locator('.workspace-filters').evaluateAll((panels) => panels.map((panel) => {
            const b = panel.getBoundingClientRect(); const s = getComputedStyle(panel);
            const controls = [...panel.querySelectorAll('input,button')].filter((e) => e.getBoundingClientRect().width > 0);
            return { fit: b.x >= 0 && b.right <= innerWidth + 1 && panel.scrollWidth <= panel.clientWidth + 1,
                styled: s.backgroundImage !== 'none' && parseFloat(s.borderInlineStartWidth) >= 2,
                controlsFit: controls.every((e) => { const r = e.getBoundingClientRect(); return r.x >= b.x && r.right <= b.right + 1; }) };
        }));
        assert(state.length > 0 && state.every((s) => s.fit && s.styled && s.controlsFit), `Filter framing: ${page.url()}`);
    };
    for (const route of routes) {
        await ready(route); await framing();
        if (['/inventory/units', '/sales/invoices', '/pos'].includes(route)) await shot(`filters-light-desktop-${route.replaceAll('/', '-')}`);
    }
    checks.push('19 workspaces: themed filter band, field/button framing and desktop overflow PASS');

    await ready('/inventory/units');
    await page.keyboard.press('Alt+f');
    const search = page.getByRole('textbox', { name: 'Cari satuan', exact: true });
    assert(await search.evaluate((e) => e === document.activeElement));
    const focus = await search.evaluate((e) => ({ border: getComputedStyle(e).borderColor, panel: getComputedStyle(e.closest('.workspace-filters')).borderInlineStartColor,
        background: getComputedStyle(e).backgroundColor, button: getComputedStyle(e.parentElement.querySelector('button')).backgroundColor }));
    assert.equal(focus.border, focus.panel); assert.notEqual(focus.background, focus.button);
    await search.fill('zz-qa-no-match'); await search.press('Enter');
    await page.getByText('0 data tersedia', { exact: true }).waitFor();
    await search.fill(''); await search.press('Enter');
    await page.getByRole('button', { name: 'Halaman berikutnya', exact: true }).click();
    await page.locator('[data-table-pagination]').getByText(/Halaman 2 dari/).waitFor();
    checks.push('Alt+F, visible focus ring/accent, distinct search button, Enter search/empty/reset and pagination PASS');

    await ready('/sales/invoices');
    await page.getByLabel('Nomor invoice', { exact: true }).fill('zz-qa-no-match');
    await page.getByRole('button', { name: 'Terapkan', exact: true }).click();
    await page.waitForURL('**/*search=zz-qa-no-match*');
    await page.getByRole('button', { name: 'Reset filter', exact: true }).click();
    await page.waitForFunction(() => !new URL(location.href).searchParams.get('search'));
    checks.push('History apply and reset preserve existing request behavior PASS');

    await page.setViewportSize({ width: 1024, height: 768 });
    for (const route of ['/inventory/stock', '/sales/invoices']) { await ready(route); await framing(); }
    await shot('filters-light-tablet-history');
    await page.getByRole('button', { name: 'Tema', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
    for (const route of ['/inventory/units', '/sales/invoices', '/finance/payments/history', '/reports/operations', '/pos']) {
        await ready(route); await framing();
        assert(await page.locator('html').evaluate((e) => e.classList.contains('dark')));
        await shot(`filters-dark-tablet-${route.replaceAll('/', '-')}`);
    }
    checks.push('Light/Dark tablet master, stock, histories, reporting and POS PASS');
}
