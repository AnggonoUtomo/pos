import assert from 'node:assert/strict';

export async function checkAuditLog({ page, browser, password, url, shot, checks, errors }) {
    await page.getByRole('link', { name: 'Log Aktivitas', exact: true }).click();
    await page.waitForURL('**/platform/audit-log');
    await page.getByRole('heading', { name: 'Log Aktivitas', exact: true }).waitFor();
    const search = page.getByLabel('Pencarian', { exact: true });
    await page.keyboard.press('Alt+f'); assert(await search.evaluate((e) => e === document.activeElement));
    await search.fill('AUDIT-'); await page.keyboard.press('Control+Enter');
    await page.locator('[data-table-pagination]').getByText(/dari 25 data/).waitFor();
    assert.equal(await page.getByRole('button', { name: /^Detail aktivitas / }).count(), 10);
    await page.getByRole('button', { name: 'Halaman berikutnya', exact: true }).click();
    await page.locator('[data-table-pagination]').getByText(/Halaman 2 dari/).waitFor();
    await page.getByRole('combobox', { name: 'Baris per halaman' }).click();
    await page.getByRole('option', { name: '25', exact: true }).click();
    await page.locator('[data-table-pagination]').getByText(/Halaman 1 dari 1/).waitFor();
    assert.equal(await page.getByRole('button', { name: /^Detail aktivitas / }).count(), 25);
    checks.push('Sidebar, Alt+F, Ctrl+Enter, literal search, global pagination and filter preservation PASS');
    await shot('audit-light-desktop');

    await page.getByLabel('Pengguna', { exact: true }).click(); await page.getByRole('option', { name: 'Sistem', exact: true }).click();
    await page.getByLabel('Modul', { exact: true }).click(); await page.getByRole('option', { name: 'Barang', exact: true }).click();
    await page.getByLabel('Tindakan', { exact: true }).click(); await page.getByRole('option', { name: 'Diubah (updated)', exact: true }).click();
    await page.getByLabel('Dari tanggal (WIB)', { exact: true }).fill('2026-10-30');
    await page.getByLabel('Sampai tanggal (WIB)', { exact: true }).fill('2026-10-30');
    await page.getByRole('button', { name: 'Terapkan', exact: true }).click();
    await page.locator('[data-table-pagination]').getByText(/dari 1 data/).waitFor();
    const row = page.getByRole('button', { name: /^Detail aktivitas / }).first();
    const id = (await row.getAttribute('aria-label')).replace('Detail aktivitas ', '');
    const marker = 'audit-private-marker';
    const result = await page.request.get(`${url}/platform/audit-log/${id}`); assert.equal(result.status(), 200);
    assert(!(await result.text()).includes(marker));
    const baselineErrors = errors.length;
    await page.route(`**/platform/audit-log/${id}`, async (route) => {
        await new Promise((resolve) => setTimeout(resolve, 300));
        await route.fulfill({ status: 503, contentType: 'application/json', body: '{"message":"Temporarily unavailable"}' });
    }, { times: 1 });
    await row.click(); await page.getByRole('status').filter({ hasText: 'Memuat detail' }).waitFor();
    await page.getByRole('alert').filter({ hasText: 'Detail aktivitas belum dapat dimuat.' }).waitFor();
    await page.getByRole('button', { name: 'Muat ulang detail', exact: true }).click();
    const dialog = page.getByRole('dialog', { name: 'Detail aktivitas', exact: true });
    await dialog.getByText('Barang lama', { exact: true }).waitFor();
    await dialog.getByText('Barang baru', { exact: true }).waitFor();
    await dialog.getByText('123.456.789.012.345,123456', { exact: true }).waitFor();
    assert(!(await dialog.innerText()).includes(marker));
    assert.equal(await dialog.locator('input, textarea').count(), 0);
    for (let i = errors.length - 1; i >= baselineErrors; i--) { if (/503/.test(errors[i])) errors.splice(i, 1); }
    await shot('audit-detail-light'); await page.keyboard.press('Escape');
    checks.push('Module/actor/event/WIB filters; detail loading, controlled 503/retry, redaction and exact Indonesian decimal PASS');

    await search.fill('zz-qa-no-match'); await search.press('Enter');
    await page.getByText('Tidak ada aktivitas yang sesuai.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Reset filter', exact: true }).click();
    await page.waitForFunction(() => !new URL(location.href).searchParams.get('search'));
    await page.keyboard.press('F1'); await page.getByRole('heading', { name: 'Panduan Log Aktivitas', exact: true }).waitFor();
    await page.keyboard.press('Escape'); await page.keyboard.press('Control+k');
    await page.getByPlaceholder('Cari menu atau operasi...').fill('Log Aktivitas');
    await page.getByRole('button', { name: /Log Aktivitas Telusuri aktivitas/ }).click();
    await page.getByRole('heading', { name: 'Log Aktivitas', exact: true }).waitFor();
    await search.fill('AUDIT-'); await search.press('Enter');
    await page.locator('[data-table-pagination]').getByText(/dari 25 data/).waitFor();
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.getByRole('button', { name: 'Tema', exact: true }).click(); await page.getByRole('menuitem', { name: 'Dark', exact: true }).click();
    await shot('audit-dark-tablet');
    await page.getByRole('button', { name: /^Detail aktivitas / }).first().click();
    await page.getByRole('table', { name: 'Perubahan aktivitas' }).waitFor();
    const framed = await page.getByRole('dialog', { name: 'Detail aktivitas', exact: true }).evaluate((e) => { const r = e.getBoundingClientRect(); return r.x >= 0 && r.y >= 0 && r.right <= innerWidth && r.bottom <= innerHeight; });
    assert(framed); await shot('audit-detail-dark-tablet'); await page.keyboard.press('Escape');
    checks.push('Empty/reset, F1 guide, command palette and desktop/tablet Light/Dark framing PASS');

    for (const role of ['auditor', 'limited']) {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        try {
            await context.route('https://fonts.bunny.net/**', (route) => route.fulfill({ contentType: 'text/css', body: '' }));
            const tab = await context.newPage();
            await tab.goto(`${url}/login`); await tab.getByLabel('Email address').fill(`qa-${role}@example.test`);
            await tab.getByLabel('Password', { exact: true }).fill(password); await tab.getByRole('button', { name: 'Log in', exact: true }).click(); await tab.waitForURL('**/dashboard');
            assert.equal(await tab.getByRole('link', { name: 'Log Aktivitas', exact: true }).count(), role === 'auditor' ? 1 : 0);
            for (const path of ['/platform/audit-log', '/platform/audit-log/data', `/platform/audit-log/${id}`]) {
                assert.equal((await context.request.get(`${url}${path}`)).status(), role === 'auditor' ? 200 : 403);
            }
        } finally { await context.close(); }
    }
    checks.push('Dedicated non-super permission allows all read endpoints; unauthorized role hides navigation and receives 403 PASS');
}
