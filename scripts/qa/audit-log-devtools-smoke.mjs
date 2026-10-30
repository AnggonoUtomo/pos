import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import path from 'node:path';
import { createInterface } from 'node:readline';
import { pathToFileURL } from 'node:url';

export async function auditDevtoolsSmoke({ url, password, artifacts, entry }) {
    const process = spawn(globalThis.process.execPath, [entry, '--headless', '--isolated', '--no-usage-statistics'], { stdio: ['pipe', 'pipe', 'ignore'], windowsHide: true });
    const reader = createInterface({ input: process.stdout });
    const pending = new Map(); let sequence = 0; let pageId;
    const call = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`MCP timeout: ${method}`)); }, 25000);
        pending.set(id, { resolve, reject, timer });
        process.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id, method, params })}\n`);
    });
    reader.on('line', (line) => {
        let message; try { message = JSON.parse(line); } catch { return; }
        if (message.method === 'roots/list') {
            process.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', id: message.id, result: { roots: [{ uri: pathToFileURL(globalThis.process.cwd()).href, name: 'POS' }] } })}\n`);
            return;
        }
        const request = pending.get(message.id); if (!request) return;
        pending.delete(message.id); clearTimeout(request.timer);
        if (message.error) request.reject(new Error('MCP request gagal.')); else request.resolve(message.result);
    });
    const tool = async (name, args = {}) => {
        const result = await call('tools/call', { name, arguments: pageId === undefined ? args : { pageId, ...args } });
        assert(!result.isError, `MCP tool gagal: ${name}`);
        return result.content.filter((c) => c.type === 'text').map((c) => c.text).join('\n');
    };
    const snapshot = () => tool('take_snapshot');
    const locate = async (role, label) => {
        for (let i = 0; i < 20; i++) {
            const text = await snapshot();
            const line = text.split('\n').find((line) => line.includes(`${role} "${label}"`) && !line.includes('disabled'));
            if (line) return line.match(/uid=(\S+)/)[1];
            await new Promise((resolve) => setTimeout(resolve, 150));
        }
        throw new Error(`MCP elemen belum siap: ${label}`);
    };
    try {
        await call('initialize', { protocolVersion: '2024-11-05', capabilities: { roots: {} }, clientInfo: { name: 'pos-audit-log-qa', version: '1' } });
        process.stdin.write(`${JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' })}\n`);
        const opened = await tool('new_page', { url: `${url}/login` });
        pageId = Number(opened.match(/(?:^|\n)(\d+):[^\n]*\[selected\]/)?.[1]);
        assert(Number.isInteger(pageId), 'MCP page ID tidak ditemukan.');
        await tool('fill_form', { elements: [
            { uid: await locate('textbox', 'Email address'), value: 'qa-manager@example.test' },
            { uid: await locate('textbox', 'Password'), value: password },
        ] });
        await tool('click', { uid: await locate('button', 'Log in') });
        await locate('heading', 'Dasbor Operasional');
        await tool('navigate_page', { url: `${url}/platform/audit-log` });
        await locate('heading', 'Log Aktivitas');
        await tool('resize_page', { width: 1440, height: 1000 });
        const text = await snapshot();
        assert(!text.includes('audit-private-marker'));
        const output = await tool('evaluate_script', { function: '() => ({overflow:document.documentElement.scrollWidth>innerWidth, filter:!!document.querySelector(".workspace-filters"), table:!!document.querySelector("table.workspace-table")})' });
        const result = JSON.parse(output.match(/```json\s*([\s\S]*?)```/)[1]);
        assert(!result.overflow && result.filter && result.table);
        await tool('take_screenshot', { filePath: path.join(artifacts, 'audit-mcp-desktop.png') });
        return 'Chrome DevTools MCP existing bin/stdio isolated: DOM, auth, readonly projection and screenshot PASS';
    } finally {
        for (const item of pending.values()) clearTimeout(item.timer);
        process.stdin.end(); reader.close();
        await new Promise((resolve) => {
            const timer = setTimeout(() => { process.kill(); resolve(); }, 3000);
            process.once('exit', () => { clearTimeout(timer); resolve(); });
        });
    }
}
