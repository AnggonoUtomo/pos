import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile, unlink } from 'node:fs/promises';
import { createServer } from 'node:net';
import path from 'node:path';
import { createInterface } from 'node:readline';
import { fileURLToPath, pathToFileURL } from 'node:url';

assert(process.env.QA_DEVTOOLS_ENTRY, 'Gunakan executable Chrome DevTools MCP existing.');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const mcp = spawn(process.execPath, [process.env.QA_DEVTOOLS_ENTRY, '--headless', '--isolated', '--no-usage-statistics'],
    { stdio: ['pipe', 'pipe', 'ignore'], windowsHide: true });
const lines = createInterface({ input: mcp.stdout });
const pending = new Map();
let sequence = 0;
let server;
let database;
lines.on('line', (line) => {
    let message;
    try { message = JSON.parse(line); } catch { return; }
    if (message.method === 'roots/list') {
        mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id: message.id, result: { roots: [{ uri: pathToFileURL(root).href, name: 'POS' }] } })}\n`);
        return;
    }
    const request = pending.get(message.id);
    if (!request) return;
    pending.delete(message.id);
    clearTimeout(request.timer);
    if (message.error) request.reject(new Error(message.error.message));
    else request.resolve(message.result);
});
const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence;
    const timer = setTimeout(() => { pending.delete(id); reject(new Error(`MCP timeout: ${method}`)); }, 45000);
    pending.set(id, { resolve, reject, timer });
    mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id, method, params })}\n`);
});

try {
    await call('initialize', { protocolVersion: '2024-11-05', capabilities: { roots: { listChanged: false } },
        clientInfo: { name: 'pos-transfer-draft-qa', version: '1.0' } });
    mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' })}\n`);
    const id = randomBytes(8).toString('hex');
    const artifacts = path.join(root, 'storage/framework/testing', `mvp-browser-${id}`);
    await mkdir(artifacts, { recursive: true });
    database = `${artifacts}.sqlite`;
    await writeFile(database, '');
    const port = await new Promise((resolve, reject) => {
        const socket = createServer();
        socket.once('error', reject);
        socket.listen(0, '127.0.0.1', () => { const value = socket.address().port; socket.close(() => resolve(value)); });
    });
    const url = `http://127.0.0.1:${port}`;
    const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite',
        DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `return_post_${id}`,
        SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
    const fixture = (input) => {
        const result = spawnSync('php', ['tests/Browser/mvp-checkout-fixture.php'],
            { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
        assert.equal(result.status, 0, 'Fixture QA gagal.');
        return JSON.parse(result.stdout);
    };
    const password = randomBytes(32).toString('hex');
    const setup = fixture({ password });
    assert.equal(setup.status, 'READY');
    console.log('PASS: fixture MVP disposable siap');
    server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
        { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
    let ready = false;
    for (let attempt = 0; attempt < 40; attempt++) {
        try {
            const response = await fetch(`${url}/login`, { signal: AbortSignal.timeout(2500) });
            await response.arrayBuffer();
            if (response.ok) { ready = true; break; }
        } catch { /* Wait for the isolated server. */ }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    assert(ready, 'Server QA belum siap.');
    const tool = async (name, args = {}) => {
        const result = await call('tools/call', { name, arguments: args });
        assert(!result.isError, `Chrome DevTools gagal: ${name}`);
        return result.content.filter((content) => content.type === 'text').map((content) => content.text).join('\n');
    };
    const snapshot = (pageId) => tool('take_snapshot', { pageId });
    const until = async (pageId, predicate, label) => {
        let last = '';
        for (let attempt = 0; attempt < 60; attempt++) {
            const current = await snapshot(pageId);
            last = current;
            if (predicate(current)) return current;
            await new Promise((resolve) => setTimeout(resolve, 200));
        }
        if (label === 'Bayar dan posting') {
            await writeFile(path.join(artifacts, 'payment-failure-snapshot.txt'), last);
            await tool('take_screenshot', {pageId, filePath:path.join(artifacts, 'payment-failure.png')});
        }
        throw new Error(`Timeout QA: ${label}; artifacts ${path.relative(root, artifacts)}`);
    };
    const locate = async (pageId, role, label) => {
        const current = await until(pageId, (value) => value.split('\n').some((line) =>
            line.includes(`${role} "${label}"`) && !line.includes('disabled')), label);
        return current.split('\n').find((line) => line.includes(`${role} "${label}"`) && !line.includes('disabled')).match(/uid=(\S+)/)[1];
    };
    const click = async (pageId, role, label) => tool('click', { pageId, uid: await locate(pageId, role, label) });
    const inspect = async (pageId, source) => {
        const output = await tool('evaluate_script', { pageId, function: source, waitForStableDom: false });
        return JSON.parse(output.match(/```json\s*([\s\S]*?)```/)[1]);
    };
    const login = async (role) => {
        const opened = await tool('new_page', { url: `${url}/login`, isolatedContext: `purchase-return-${role}-${id}` });
        const pageId = Number(opened.match(/(?:^|\n)(\d+):[^\n]*\[selected\]/)?.[1]);
        assert(Number.isInteger(pageId), 'ID halaman QA tidak ditemukan.');
        await tool('fill_form', { pageId, elements: [
            { uid: await locate(pageId, 'textbox', 'Email address'), value: `qa-${role}@example.test` },
            { uid: await locate(pageId, 'textbox', 'Password'), value: password },
        ] });
        await click(pageId, 'button', 'Log in');
        await locate(pageId, 'heading', 'Dasbor Operasional');
        await tool('navigate_page', { pageId, url: `${url}/platform/company-settings` });
        return pageId;
    };
    const pageId = await login('manager');

    if (process.argv.includes('--navigation-only')) {
        await tool('resize_page', {pageId, width:1024, height:768});
        const layout = await inspect(pageId, `() => {
            const link = [...document.querySelectorAll('a')].find(a => a.getAttribute('href') === '/platform/company-settings');
            const label = link?.querySelector('span');
            const badge = link?.closest('li')?.querySelector('[data-sidebar="menu-badge"]');
            if (!label || !badge) return {valid:false};
            const range = document.createRange(); range.selectNodeContents(label);
            return {valid:range.getBoundingClientRect().right + 4 <= badge.getBoundingClientRect().left, overflow:document.documentElement.scrollWidth>innerWidth};
        }`);
        assert(layout.valid && !layout.overflow, 'Label dan badge navigasi bertumpuk.');
        await tool('take_screenshot', {pageId,filePath:path.join(artifacts,'tablet-navigation.png')});
        console.log(JSON.stringify({status:'PASS',checks:['tablet navigation label/badge nonoverlap'],artifacts:path.relative(root,artifacts)}));
    } else {


    await tool('resize_page', { pageId, width: 1440, height: 1000 });
    await tool('fill_form', { pageId, elements: [
        {uid: await locate(pageId, 'textbox', 'Nama toko'), value: 'Toko CCTV & Listrik QA'},
        {uid: await locate(pageId, 'textbox', 'Catatan struk'), value: 'Terima kasih'}
    ]});
    await click(pageId, 'button', 'Simpan pengaturan');
    await until(pageId, current => current.includes('Pengaturan toko tersimpan.'), 'company saved');
    console.log('PASS: pengaturan toko');
    await tool('navigate_page', {pageId, url: `${url}/purchasing/purchase-orders`});
    await click(pageId, 'button', `Terima dan bayar ${setup.purchase_number}`);
    await locate(pageId, 'dialog', 'Terima dan bayar pembelian');
    await click(pageId, 'button', 'Catat pembayaran');
    await until(pageId, current => !current.includes('dialog "Terima dan bayar pembelian"') && current.includes('Lunas'), 'purchase paid');
    console.log('PASS: pembelian diterima dan lunas');
    await tool('navigate_page', {pageId, url: `${url}/pos`});
    const choose = async (label, value) => {await click(pageId, 'combobox', label); await click(pageId, 'option', value);};
    await choose('Gudang', 'Gudang Utama');
    await choose('Pelanggan', 'Installer CCTV Demo');
    await click(pageId, 'button', 'Tambah Kamera IP 2MP');
    await choose('Jenis diskon', '%');
    await tool('fill_form', {pageId, elements: [
        {uid: await locate(pageId, 'textbox', 'Kuantitas Kamera IP 2MP'), value: '2'},
        {uid: await locate(pageId, 'textbox', 'Nilai diskon'), value: '10'},
        {uid: await locate(pageId, 'textbox', 'Pajak (%)'), value: '11'}
    ]});
    await click(pageId, 'button', 'Periksa keranjang');
    await until(pageId, current => current.includes('Rp 199.800'), 'discount and tax quote');
    console.log('PASS: quote diskon/pajak');
    await click(pageId, 'button', 'Pembayaran');
    await writeFile(path.join(artifacts, 'payment-open-snapshot.txt'), await snapshot(pageId));
    await tool('take_screenshot', {pageId, filePath:path.join(artifacts, 'payment-open.png')});
    await click(pageId, 'button', 'Bayar dan posting');
    await locate(pageId, 'dialog', 'Bukti penjualan');
    const receipt = await snapshot(pageId);
    assert(receipt.includes('Toko CCTV & Listrik QA') && receipt.includes('199.800') && receipt.includes('20.000') && receipt.includes('19.800'), 'Snapshot receipt tidak sesuai');
    await tool('take_screenshot', {pageId, filePath:path.join(artifacts, 'desktop-receipt.png')});
    await click(pageId, 'button', 'Selesai');
    await tool('navigate_page', {pageId, url: `${url}/reports/operations`});
    await locate(pageId, 'heading', 'Laporan operasional');
    await until(pageId, current => current.includes('199.800') && current.includes('Arus pembayaran seluruh gudang'), 'report');
    await tool('resize_page', {pageId, width:1024, height:768});
    const frame = await inspect(pageId, '() => ({overflow:document.documentElement.scrollWidth>innerWidth})');
    assert(!frame.overflow, 'Overflow laporan tablet');
    await tool('take_screenshot', {pageId, filePath:path.join(artifacts,'tablet-report.png')});
    const errors = await tool('list_console_messages', {pageId, types:['error']});
    assert(errors.includes('<no console messages found>'), 'Console errors');
    const state=fixture({verify:true});
    const report={status:'PASS',checks:['company update','receive and pay purchase','discount/tax paid checkout','receipt snapshots','operational report desktop/tablet','clean console'],state,artifacts:path.relative(root,artifacts)};
    await writeFile(path.join(artifacts,'devtools-report.json'),JSON.stringify(report,null,2));
    console.log(JSON.stringify(report));
    }
} finally {
    for (const request of pending.values()) clearTimeout(request.timer);
    mcp.stdin.end();
    lines.close();
    await new Promise((resolve) => {
        const timer = setTimeout(() => { mcp.kill(); resolve(); }, 3000);
        mcp.once('exit', () => { clearTimeout(timer); resolve(); });
    });
    if (server && server.exitCode === null) { server.kill(); await new Promise((resolve) => server.once('exit', resolve)); }
    if (database) await unlink(database).catch(() => {});
}
