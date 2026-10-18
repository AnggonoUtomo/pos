import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { createInterface } from 'node:readline';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createServer } from 'node:net';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

// Jalankan MCP yang sudah terpasang, tanpa npx install atau profil Chrome user.
assert(process.env.QA_DEVTOOLS_ENTRY, 'QA_DEVTOOLS_ENTRY wajib menunjuk executable MCP existing.');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const posting = process.argv.includes('--posting');
const mcp = spawn(process.execPath, [process.env.QA_DEVTOOLS_ENTRY, '--headless', '--isolated', '--no-usage-statistics'], { stdio: ['pipe', 'pipe', 'ignore'], windowsHide: true });
let sequence = 0;
const pending = new Map();
const lines = createInterface({ input: mcp.stdout });
let server;
lines.on('line', (line) => {
    let result; try { result = JSON.parse(line); } catch { return; }
    if (result.method === 'roots/list') {
        mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id: result.id, result: { roots: [{ uri: pathToFileURL(root).href, name: 'POS workspace' }] } })}\n`);
        return;
    }
    const request = pending.get(result.id); if (!request) return;
    pending.delete(result.id); clearTimeout(request.timer);
    if (result.error) request.reject(new Error(result.error.message)); else request.resolve(result.result);
});
const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence;
    const timer = setTimeout(() => { pending.delete(id); reject(new Error(`MCP timeout: ${method}`)); }, 45000);
    pending.set(id, { resolve, reject, timer });
    mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id, method, params })}\n`);
});
try {
    await call('initialize', { protocolVersion: '2024-11-05', capabilities: { roots: { listChanged: false } }, clientInfo: { name: 'pos-returns-qa', version: '1.0' } });
    mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' })}\n`);
    const tools = await call('tools/list');
    if (process.argv.includes('--inspect-tools')) console.log(JSON.stringify(tools.tools.filter((tool) => /navigate|snapshot|fill|click|screenshot|console|resize|network/.test(tool.name)), null, 2));
    else {
        const id = randomBytes(8).toString('hex');
        const artifacts = path.join(root, 'storage/framework/testing', `sales-returns-browser-${id}`);
        await mkdir(artifacts, { recursive: true }); const database = `${artifacts}.sqlite`; await writeFile(database, '');
        const port = await new Promise((resolve, reject) => { const s = createServer(); s.once('error', reject); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
        const url = `http://127.0.0.1:${port}`;
        const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `returns_mcp_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
        const fixture = (input) => { const r = spawnSync('php', ['tests/Browser/sales-returns-fixture.php'], { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true }); assert.equal(r.status, 0, 'Fixture MCP gagal.'); return JSON.parse(r.stdout); };
        const password = randomBytes(32).toString('hex'); const setup = fixture({ password, posting });
        server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
        let ready = false;
        for (let i = 0; i < 20; i++) { try { const r = await fetch(`${url}/login`, { signal: AbortSignal.timeout(2500) }); await r.arrayBuffer(); if (r.ok) { ready = true; break; } } catch { /* Await QA server. */ } await new Promise((r) => setTimeout(r, 250)); }
        assert(ready, 'Server QA belum siap.');
        const tool = async (name, args = {}) => {
            const result = await call('tools/call', { name, arguments: args });
            assert(!result.isError, `DevTools gagal: ${name}${name === 'take_screenshot' ? ': ' + result.content.filter((c) => c.type === 'text').map((c) => c.text).join(' ') : ''}`);
            return result.content.filter((c) => c.type === 'text').map((c) => c.text).join('\n');
        };
        const opened = await call('tools/call', { name: 'new_page', arguments: { url: `${url}/login`, timeout: 15000 } });
        assert(!opened.isError, 'MCP gagal membuka login QA.');
        const pageId = opened.structuredContent?.pages?.find((page) => page.url === `${url}/login`)?.id
            ?? Number(opened.content.filter((c) => c.type === 'text').map((c) => c.text).join('\n').match(/(?:^|\n)(\d+):[^\n]*http/m)?.[1]);
        assert(Number.isInteger(pageId), 'Page ID MCP tidak ditemukan.');
        const snapshot = () => tool('take_snapshot', { pageId });
        const locate = async (role, label, partial = false) => {
            for (let i = 0; i < 40; i++) {
                const text = await snapshot();
                const row = text.split('\n').find((line) => line.includes(`${role} "${label}${partial ? '' : '"'}`) && !line.includes('disabled'));
                if (row) return row.match(/uid=(\S+)/)[1];
                await new Promise((r) => setTimeout(r, 250));
            }
            throw new Error(`Elemen MCP tidak ditemukan: ${role} ${label}`);
        };
        const click = async (role, label, partial = false) => tool('click', { pageId, uid: await locate(role, label, partial) });
        await tool('fill_form', { pageId, elements: [{ uid: await locate('textbox', 'Email address'), value: 'qa-manager@example.test' }, { uid: await locate('textbox', 'Password'), value: password }] });
        await click('button', 'Log in');
        await locate('heading', 'Dasbor Operasional');
        await tool('navigate_page', { pageId, type: 'url', url: `${url}/sales/returns`, timeout: 15000 });
        await locate('heading', 'Retur penjualan'); console.log('QA DevTools: halaman retur siap.');
        await click('button', 'Tambah draft retur'); await click('combobox', 'Invoice asal');
        await click('option', setup.number, true);
        await tool('fill', { pageId, uid: await locate('textbox', `Kuantitas retur ${setup.sku}`), value: '1,25' });
        await click('button', 'Simpan draft');
        let number;
        for (let i = 0; i < 40; i++) { const text = await snapshot(); number = text.match(/SR-[A-Z0-9]{26}/)?.[0]; if (number && !text.includes('dialog "Tambah draft retur"')) break; await new Promise((r) => setTimeout(r, 250)); }
        assert(number, 'Draft belum tersimpan.');
        if (posting) {
            await click('button', `Terima ${number}`); await click('button', 'Terima barang');
            await locate('button', `Detail ${number}`);
            assert(!(await snapshot()).includes(`button "Edit ${number}"`), 'Posted masih dapat diedit.');
        }
        await click('button', `Detail ${number}`);
        assert((await snapshot()).includes('1,25'), 'Format qty Indonesia tidak tampil.');
        const screenshots = [];
        for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
            await tool('resize_page', { pageId, width, height });
            const result = await tool('evaluate_script', { pageId, function: '() => ({ overflow: document.documentElement.scrollWidth > innerWidth, dialog: (() => { const r = document.querySelector("[role=dialog]").getBoundingClientRect(); return r.x >= 0 && r.y >= 0 && r.right <= innerWidth + 1 && r.bottom <= innerHeight + 1; })() })' });
            assert(/"overflow"\s*:\s*false/.test(result) && /"dialog"\s*:\s*true/.test(result), 'Framing DevTools gagal.');
            const filePath = path.join(artifacts, `devtools-detail-${width}.png`); await tool('take_screenshot', { pageId, filePath }); screenshots.push(path.basename(filePath));
        }
        const consoleMessages = await tool('list_console_messages', { pageId, types: ['error', 'warn'] });
        assert(!/Uncaught|Warning:|TypeError|ReferenceError/i.test(consoleMessages), 'Error runtime DevTools.');
        const network = await tool('list_network_requests', { pageId, resourceTypes: ['document', 'xhr', 'fetch', 'script', 'stylesheet'] });
        assert(!network.split('\n').some((line) => line.includes(url) && /failed|status: [45]\d\d/i.test(line)), 'Request aplikasi DevTools gagal.');
        const state = fixture({ verify: setup.fingerprint, posting, expected_quantity: '1.25', initial_balance: setup.initial_balance }); assert.deepEqual(state.events, ['created', ...(posting ? ['posted'] : [])]);
        const report = { status: 'PASS', posting, method: 'Chrome DevTools MCP stdio existing, isolated profile', checks: ['login, source lookup, create, optional posting/readonly, snapshot detail, Indonesian quantity, desktop/tablet framing', 'source invoice/consumption/finance unchanged; stock delta verified', 'console/runtime and internal network checked'], screenshots, artifacts: path.relative(root, artifacts) };
        await writeFile(path.join(artifacts, 'devtools-report.json'), JSON.stringify(report, null, 2)); console.log(JSON.stringify(report, null, 2));
    }
} finally {
    for (const request of pending.values()) clearTimeout(request.timer);
    mcp.stdin.end(); lines.close();
    await new Promise((resolve) => { const timer = setTimeout(() => { mcp.kill(); resolve(); }, 3000); mcp.once('exit', () => { clearTimeout(timer); resolve(); }); });
    server?.kill();
}
