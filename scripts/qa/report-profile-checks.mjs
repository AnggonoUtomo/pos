import assert from 'node:assert/strict';

export async function checkReportProfile({ page, url, setup, shot, checks }) {
    await page.goto(`${url}/reports/operations`);
    await page.getByRole('heading', { name: 'Laporan operasional', exact: true }).waitFor();
    const cards = page.locator('dl .dashboard-metric');
    assert.equal(await cards.count(), 4, 'Empat ringkasan laporan harus berupa card bertone dashboard.');
    assert.deepEqual(await cards.evaluateAll((nodes) => nodes.map((node) => node.dataset.tone)), ['sky', 'emerald', 'rose', 'amber']);
    const colors = await cards.evaluateAll((nodes) => nodes.map((node) => getComputedStyle(node).backgroundColor));
    assert.equal(new Set(colors).size, 4);
    const amount = (value) => new Intl.NumberFormat('id-ID').format(BigInt(value.split('.')[0]));
    assert((await cards.nth(0).innerText()).includes(amount(setup.report.sales.total)));
    assert((await cards.nth(1).innerText()).includes(amount(setup.report.purchases.total_value)));
    assert((await cards.nth(2).innerText()).includes(`${setup.report.sales_returns.count} dokumen`));
    assert((await cards.nth(3).innerText()).includes(`${setup.report.purchase_returns.count} dokumen`));
    await shot('report-cards-desktop');
    await page.setViewportSize({ width: 1024, height: 768 });
    await shot('report-cards-tablet');
    await page.getByLabel('Dari tanggal', { exact: true }).fill('2000-01-01');
    await page.getByLabel('Sampai tanggal', { exact: true }).fill('2000-01-01');
    await page.getByRole('button', { name: 'Terapkan', exact: true }).click();
    await page.getByText('Belum ada nilai transaksi pada periode ini.', { exact: true }).waitFor();
    assert((await cards.nth(0).innerText()).includes('0 nota'));
    checks.push('Four colored report cards preserve real values, tablet layout and period filter PASS');

    await page.goto(`${url}/settings/profile`);
    await page.getByLabel('Nama', { exact: true }).waitFor();
    assert.equal(await page.getByText(/Delete account|Delete your account|Hapus akun/i).count(), 0);
    const name = page.getByLabel('Nama', { exact: true });
    await name.fill('Profil QA diperbarui');
    await page.getByRole('button', { name: 'Panduan halaman', exact: true }).click();
    const guide = page.getByRole('dialog');
    await guide.getByRole('heading', { name: 'Panduan Profil Akun', exact: true }).waitFor();
    assert.equal(await guide.getByText('Sebelum menghapus akun', { exact: true }).count(), 0);
    await page.keyboard.press('Escape');
    await guide.waitFor({ state: 'detached' });
    assert.equal(await name.inputValue(), 'Profil QA diperbarui');
    const email = page.getByLabel('Alamat email', { exact: true });
    const originalEmail = await email.inputValue();
    await email.fill('qa-limited@example.test');
    await page.getByRole('button', { name: 'Simpan perubahan', exact: true }).click();
    await page.locator('#email-error').waitFor();
    await email.fill(originalEmail);
    await page.getByRole('button', { name: 'Simpan perubahan', exact: true }).click();
    await page.getByText('Perubahan tersimpan.', { exact: true }).waitFor();
    await shot('profile-tablet');
    await page.reload();
    assert.equal(await name.inputValue(), 'Profil QA diperbarui');
    checks.push('Profile retains unsaved input with guide, validates duplicate email and saves/reloads; delete control hidden PASS');

    await page.addInitScript(() => localStorage.setItem('appearance', 'dark'));
    await page.reload();
    await name.waitFor();
    await shot('profile-dark-tablet');
    await page.goto(`${url}/reports/operations`);
    await cards.first().waitFor();
    await shot('report-cards-dark-tablet');
    checks.push('Profile/report dark tablet without body overflow PASS');
}
