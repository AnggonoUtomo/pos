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
    const artifacts = path.join(root, 'storage/framework/testing', `transfer-draft-browser-${id}`);
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
        SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
    const fixture = (input) => {
        const result = spawnSync('php', ['tests/Browser/transfer-draft-fixture.php'],
            { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
        assert.equal(result.status, 0, 'Fixture QA gagal.');
        return JSON.parse(result.stdout);
    };
    const password = randomBytes(32).toString('hex');
    const setup = fixture({ password, operational: true });
    assert.equal(setup.status, 'READY');
    console.log('PASS: fixture retur pembelian disposable siap');
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
        for (let attempt = 0; attempt < 60; attempt++) {
            const current = await snapshot(pageId);
            if (predicate(current)) return current;
            await new Promise((resolve) => setTimeout(resolve, 200));
        }
        throw new Error(`Timeout QA: ${label}`);
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
        await tool('navigate_page', { pageId, url: `${url}/inventory/transfers` });
        return pageId;
    };
    const pageId = await login('manager');

    await tool('resize_page', { pageId, width: 1440, height: 1000 });
    await click(pageId, 'button', 'Tambah draft transfer');
    const choose = async (label, value) => { await click(pageId, 'combobox', label); await click(pageId, 'option', value); };
    await choose('Gudang asal', 'WH-MAIN - Gudang Utama');
    await choose('Gudang tujuan', 'WH-SECONDARY - Gudang Tambahan');
    await choose('Barang 1', 'DEMO-CCTV-IP-2MP - Kamera IP 2MP');
    await choose('Satuan 1', 'Unit');
    await tool('fill_form', { pageId, elements: [{ uid: await locate(pageId, 'textbox', 'Kuantitas 1'), value: '1' }] });
    await tool('press_key', { pageId, key: 'Control+Enter' });
    await until(pageId, current => !current.includes('dialog "Tambah draft transfer"') && current.includes('Posting TR-'), 'draft');
    const number = (await inspect(pageId, '() => ({number:document.querySelector("tbody tr strong")?.textContent})')).number;
    await click(pageId, 'button', `Posting ${number}`);
    await tool('press_key', { pageId, key: 'Control+Enter' });
    await until(pageId, current => current.includes('Posted') && !current.includes('dialog "Posting transfer stok?"'), 'posted');
    await tool('navigate_page', { pageId, url: `${url}/inventory/stock?tab=balances` });
    await click(pageId, 'button', 'Koreksi stok');
    await choose('Gudang koreksi', 'WH-SECONDARY - Gudang Tambahan');
    await choose('Barang koreksi', 'DEMO-CCTV-IP-2MP - Kamera IP 2MP');
    await tool('fill_form', { pageId, elements: [{ uid: await locate(pageId, 'textbox', 'Kuantitas selisih (Unit)'), value: '0,5' },
        { uid: await locate(pageId, 'textbox', 'Alasan koreksi'), value: 'Koreksi hasil hitung fisik QA' }] });
    await tool('take_screenshot', { pageId, filePath: path.join(artifacts, 'desktop-adjustment.png') });
    await tool('press_key', { pageId, key: 'Control+Enter' });
    await until(pageId, current => !current.includes('dialog "Koreksi stok"'), 'adjusted');
    await tool('resize_page', { pageId, width: 1024, height: 768 });
    const frame = await inspect(pageId, '() => ({overflow:document.documentElement.scrollWidth>innerWidth})');
    assert(!frame.overflow, 'Overflow tablet');
    await tool('take_screenshot', { pageId, filePath: path.join(artifacts, 'tablet-balances.png') });
    const messages = await tool('list_console_messages', { pageId, types: ['error'] });
    assert(messages.includes('<no console messages found>'), 'Console error');
    const state = fixture({verify: true, operational: true});
    const report = {status: 'PASS', checks: ['transfer posted through UI', 'audited adjustment Indonesian 0,5', 'desktop/tablet framing', 'clean console'], state, artifacts: path.relative(root, artifacts)};
    await writeFile(path.join(artifacts, 'devtools-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report));
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


