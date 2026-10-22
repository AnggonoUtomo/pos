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
        clientInfo: { name: 'pos-purchase-return-posting-qa', version: '1.0' } });
    mcp.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' })}\n`);
    const id = randomBytes(8).toString('hex');
    const artifacts = path.join(root, 'storage/framework/testing', `purchasing-returns-browser-${id}`);
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
        const result = spawnSync('php', ['tests/Browser/purchasing-returns-fixture.php'],
            { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
        assert.equal(result.status, 0, 'Fixture QA gagal.');
        return JSON.parse(result.stdout);
    };
    const password = randomBytes(32).toString('hex');
    const setup = fixture({ password, posting: true });
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
        await tool('navigate_page', { pageId, url: `${url}/purchasing/returns` });
        return pageId;
    };
    const pageId = await login('manager');
    await click(pageId, 'button', `Serahkan ${setup.posted_number}`);
    const screenshots = [];
    const shot = async (label) => {
        const state = await inspect(pageId, '() => { const d=document.querySelector("[role=dialog]");const b=d?.getBoundingClientRect();return {overflow:document.documentElement.scrollWidth>innerWidth,framed:!b||(b.x>=0&&b.y>=0&&b.right<=innerWidth+1&&b.bottom<=innerHeight+1)}; }');
        assert(!state.overflow && state.framed, `Framing gagal: ${label}`);
        const filePath = path.join(artifacts, `${label}.png`);
        await tool('take_screenshot', { pageId, filePath });
        screenshots.push(path.basename(filePath));
    };
    for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
        await tool('resize_page', { pageId, width, height });
        await shot(`light-confirm-${width}`);
    }
    await tool('press_key', { pageId, key: 'Control+Enter' });
    await until(pageId, (current) => !current.includes('dialog "Serahkan') && current.includes('"Diserahkan"'), 'posting selesai');
    const immutable = await inspect(pageId, `() => {const row=[...document.querySelectorAll('tbody tr')].find(r=>r.innerText.includes(${JSON.stringify(setup.posted_number)}));return {posted:row?.innerText.includes('Diserahkan'),actions:[...row.querySelectorAll('button')].map(b=>b.getAttribute('aria-label'))};}`);
    assert(immutable.posted && immutable.actions.length === 1 && immutable.actions[0].startsWith('Detail '));
    await click(pageId, 'button', `Detail ${setup.posted_number}`);
    const readonly = await inspect(pageId, '() => {const d=document.querySelector("[role=dialog]");return {posted:d.innerText.includes("Diserahkan"),mutable:[...d.querySelectorAll("input,textarea")].filter(x=>!x.disabled&&!x.readOnly).length};}');
    assert(readonly.posted && readonly.mutable === 0, 'Detail posted masih mutable.');
    await shot('readonly-detail');
    await click(pageId, 'button', 'Tutup');
    await click(pageId, 'button', `Serahkan ${setup.blocked_number}`);
    await tool('press_key', { pageId, key: 'Control+Enter' });
    await until(pageId, (current) => current.includes('Sisa layer penerimaan asal tidak mencukupi.'), 'penolakan stok sumber');
    await shot('source-layer-error');
    await click(pageId, 'button', 'Batal');
    await click(pageId, 'combobox', 'Filter status retur');
    await click(pageId, 'option', 'Diserahkan');
    await until(pageId, (current) => current.includes('value="Diserahkan"') && !current.includes(`Detail ${setup.blocked_number}`), 'filter posted');
    assert.equal((await inspect(pageId, '() => ({rows:document.querySelectorAll("tbody tr").length})')).rows, 1);
    await click(pageId, 'button', 'Shortcut');
    assert((await snapshot(pageId)).includes('Simpan/konfirmasi'));
    await tool('press_key', { pageId, key: 'Escape' });
    await tool('press_key', { pageId, key: 'Control+K' });
    await locate(pageId, 'dialog', 'Command Palette');
    await tool('press_key', { pageId, key: 'Escape' });
    await tool('resize_page', { pageId, width: 1440, height: 1000 });
    await click(pageId, 'button', 'Tema');
    await click(pageId, 'menuitem', 'Dark');
    await click(pageId, 'button', 'Toggle Sidebar');
    for (const [width, height] of [[1440, 1000], [1024, 768], [768, 1024]]) {
        await tool('resize_page', { pageId, width, height });
        await shot(`dark-collapsed-${width}`);
    }
    const viewerId = await login('viewer');
    const viewer = await inspect(viewerId, '() => {const labels=[...document.querySelectorAll("button")].map(b=>b.getAttribute("aria-label")||b.innerText);return {details:labels.filter(label=>label.startsWith("Detail ")).length,mutations:labels.filter(label=>/^(Tambah draft|Serahkan |Edit |Hapus |Pulihkan )/.test(label)).length};}');
    assert(viewer.details === 2 && viewer.mutations === 0, 'Viewer melihat kontrol mutation.');
    await click(viewerId, 'button', `Detail ${setup.posted_number}`);
    assert((await snapshot(viewerId)).includes('"Diserahkan"'));
    for (const target of [pageId, viewerId]) {
        const consoleMessages = await tool('list_console_messages', { pageId: target, types: ['error', 'warn'] });
        assert(consoleMessages.includes('<no console messages found>'), 'Console QA memiliki error/warning.');
        const network = await tool('list_network_requests', { pageId: target, resourceTypes: ['document', 'xhr', 'fetch', 'script', 'stylesheet'] });
        assert(!network.split('\n').some((line) => line.includes(url) && /failed|\[[45]\d\d\]/i.test(line)), 'Request internal QA gagal.');
    }
    const state = fixture({ verify_posting: true, posted_id: setup.posted_id, blocked_id: setup.blocked_id,
        source_fingerprint: setup.source_fingerprint });
    assert.equal(state.status, 'PASS');
    const report = { status: 'PASS', method: 'Chrome DevTools MCP existing stdio isolated',
        checks: ['posting via shortcut, immutable actions', 'source layer shortage and Sonner', 'posted filter and readonly detail',
            'viewer permission controls', 'shortcut dropdown and command palette', 'light/dark desktop/tablet framing',
            'clean console and internal network', 'single issue/audit and immutable purchase/Finance'],
        screenshots, state, artifacts: path.relative(root, artifacts) };
    await writeFile(path.join(artifacts, 'devtools-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
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
