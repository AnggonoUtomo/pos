import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { createInterface } from 'node:readline';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile, unlink } from 'node:fs/promises';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

assert(process.env.QA_DEVTOOLS_ENTRY, 'Gunakan executable MCP existing.');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const mcp = spawn(process.execPath, [process.env.QA_DEVTOOLS_ENTRY, '--headless', '--isolated', '--no-usage-statistics'], { stdio: ['pipe', 'pipe', 'ignore'], windowsHide: true });
const lines = createInterface({ input: mcp.stdout });
const pending = new Map(); let sequence = 0; let server; let database;
lines.on('line', (line) => {
    let message; try { message = JSON.parse(line); } catch { return; }
    if (message.method === 'roots/list') { mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id: message.id, result: { roots: [{ uri: pathToFileURL(root).href, name: 'POS' }] } })}\n`); return; }
    const request = pending.get(message.id); if (!request) return;
    pending.delete(message.id); clearTimeout(request.timer);
    if (message.error) request.reject(new Error(message.error.message)); else request.resolve(message.result);
});
const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; const timer = setTimeout(() => { pending.delete(id); reject(new Error(`MCP timeout: ${method}`)); }, 45000);
    pending.set(id, { resolve, reject, timer }); mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id, method, params })}\n`);
});
try {
    await call('initialize', { protocolVersion: '2024-11-05', capabilities: { roots: { listChanged: false } }, clientInfo: { name: 'pos-refund-qa', version: '1.0' } });
    mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' })}\n`);
    const id = randomBytes(8).toString('hex');
    const artifacts = path.join(root, 'storage/framework/testing', `return-refund-browser-${id}`);
    await mkdir(artifacts, { recursive: true }); database = `${artifacts}.sqlite`; await writeFile(database, '');
    const port = await new Promise((resolve, reject) => { const s = createServer(); s.once('error', reject); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
    const url = `http://127.0.0.1:${port}`;
    const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `refund_mcp_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
    const fixture = (input) => { const r = spawnSync('php', ['tests/Browser/sale-return-refund-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(r.status, 0, `Fixture MCP gagal: ${r.stderr}`); return JSON.parse(r.stdout); };
    const password = randomBytes(32).toString('hex'); const setup = fixture({ password });
    server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
    let ready = false;
    for (let i = 0; i < 30; i++) { try { const r = await fetch(`${url}/login`, { signal: AbortSignal.timeout(2500) }); await r.arrayBuffer(); if (r.ok) { ready = true; break; } } catch { /* Wait for QA server. */ } await new Promise((r) => setTimeout(r, 250)); }
    assert(ready, 'Server QA belum siap.');
    const tool = async (name, args = {}) => {
        const result = await call('tools/call', { name, arguments: args });
        assert(!result.isError, `DevTools gagal: ${name}`);
        return result.content.filter((c) => c.type === 'text').map((c) => c.text).join('\n');
    };
    const opened = await tool('new_page', { url: `${url}/login`, timeout: 20000 });
    const pageId = Number(opened.match(/(?:^|\n)(\d+):[^\n]*http/m)?.[1]); assert(Number.isInteger(pageId), 'ID halaman QA tidak ditemukan.');
    const snapshot = () => tool('take_snapshot', { pageId });
    const locate = async (role, label) => {
        for (let i = 0; i < 50; i++) {
            const row = (await snapshot()).split('\n').find((line) => line.includes(`${role} "${label}"`) && !line.includes('disabled'));
            if (row) return row.match(/uid=(\S+)/)[1];
            await new Promise((r) => setTimeout(r, 250));
        } throw new Error(`Elemen QA tidak ditemukan: ${role} ${label}`);
    };
    const click = async (role, label) => tool('click', { pageId, uid: await locate(role, label) });
    await tool('fill_form', { pageId, elements: [{ uid: await locate('textbox', 'Email address'), value: 'qa-manager@example.test' }, { uid: await locate('textbox', 'Password'), value: password }] });
    await click('button', 'Log in'); await locate('heading', 'Dasbor Operasional');
    await tool('navigate_page', { pageId, type: 'url', url: `${url}/sales/returns`, timeout: 20000 });
    await click('button', `Refund ${setup.numbers[setup.first]}`); await locate('button', 'Konfirmasi refund');
    assert((await snapshot()).includes('Rp 40'), 'Nominal Indonesia salah.');
    const screenshots = [];
    for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
        await tool('resize_page', { pageId, width, height });
        const result = await tool('evaluate_script', { pageId, function: '() => ({ overflow: document.documentElement.scrollWidth > innerWidth, framed: (() => { const r = document.querySelector("[role=dialog]").getBoundingClientRect(); return r.x >= 0 && r.y >= 0 && r.right <= innerWidth + 1 && r.bottom <= innerHeight + 1; })() })' });
        assert(/"overflow"\s*:\s*false/.test(result) && /"framed"\s*:\s*true/.test(result), 'Framing refund gagal.');
        const filePath = path.join(artifacts, `devtools-refund-${width}.png`); await tool('take_screenshot', { pageId, filePath }); screenshots.push(path.basename(filePath));
    }
    await click('button', 'Konfirmasi refund'); await locate('heading', 'Hasil refund retur'); await click('button', 'Tutup');
    await click('button', `Refund ${setup.numbers[setup.zero]}`); await locate('button', 'Selesaikan tanpa uang');
    assert(!(await snapshot()).includes('combobox "Metode'), 'Refund nol masih meminta metode.');
    await click('button', 'Selesaikan tanpa uang'); await locate('heading', 'Hasil refund retur'); await click('button', 'Tutup');
    await tool('navigate_page', { pageId, type: 'url', url: `${url}/finance/payments/history?search=${encodeURIComponent(setup.numbers[setup.first])}`, timeout: 20000 });
    await click('button', `Detail ${setup.numbers[setup.first]}`); await locate('heading', 'Detail pembayaran');
    let detailReady = false;
    for (let i = 0; i < 40; i++) {
        const detail = await tool('evaluate_script', { pageId, function: '() => ({ ready: document.querySelector("[role=dialog]")?.textContent.includes("Refund retur") === true })' });
        if (/"ready"\s*:\s*true/.test(detail)) { detailReady = true; break; }
        await new Promise((r) => setTimeout(r, 250));
    }
    assert(detailReady, 'Snapshot sumber refund belum tampil pada dialog Finance.');
    const consoleMessages = await tool('list_console_messages', { pageId, types: ['error', 'warn'] });
    assert(!/Uncaught|Warning:|TypeError|ReferenceError/i.test(consoleMessages), 'Error runtime DevTools.');
    const network = await tool('list_network_requests', { pageId, resourceTypes: ['document', 'xhr', 'fetch', 'script', 'stylesheet'] });
    assert(!network.split('\n').some((line) => line.includes(url) && /failed|status: [45]\d\d/i.test(line)), 'Request internal DevTools gagal.');
    const state = fixture({ verify: setup.fingerprint, settlements: 2, refunds: 1 });
    const report = { status: 'PASS', method: 'Chrome DevTools MCP existing stdio isolated', checks: ['authenticated positive and zero settlement, readonly result, Finance detail, exact audit/source fingerprint, console/network, desktop/tablet framing'], screenshots, state, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'devtools-report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
} finally {
    for (const request of pending.values()) clearTimeout(request.timer);
    mcp.stdin.end(); lines.close();
    await new Promise((resolve) => { const timer = setTimeout(() => { mcp.kill(); resolve(); }, 3000); mcp.once('exit', () => { clearTimeout(timer); resolve(); }); });
    if (server) { server.kill(); await new Promise((resolve) => server.once('exit', resolve)); }
    if (database) await unlink(database).catch(() => {});
}
