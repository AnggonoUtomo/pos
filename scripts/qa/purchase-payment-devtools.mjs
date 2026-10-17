import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createServer } from 'node:net';
import path from 'node:path';
import { createInterface } from 'node:readline';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const id = randomBytes(8).toString('hex');
const artifacts = path.join(root, 'storage/framework/testing', `purchase-payment-browser-${id}`);
await mkdir(artifacts, { recursive: true });
const database = `${artifacts}.sqlite`; await writeFile(database, '');
const input = createInterface({ input: process.stdin, terminal: false });
const iterator = input[Symbol.asyncIterator]();
const password = process.env.QA_PAYMENT_PASSWORD ?? JSON.parse((await iterator.next()).value).password;
delete process.env.QA_PAYMENT_PASSWORD;
assert(typeof password === 'string' && password.length >= 24, 'Credential QA wajib stdin, tidak ditulis ke artefak.');
const port = await new Promise((resolve, reject) => { const s = createServer(); s.once('error', reject); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
const url = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `devtools_pay_${id}`, SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array' };
const fixture = spawnSync('php', ['tests/Browser/purchase-drafts-fixture.php'], { cwd: root, env, input: JSON.stringify({ password, payment: true }), encoding: 'utf8', windowsHide: true });
assert.equal(fixture.status, 0, 'Fixture DevTools gagal.');
const setup = JSON.parse(fixture.stdout);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], { cwd: path.join(root, 'public'), env, stdio: 'ignore', windowsHide: true });
const stop = () => { server.kill(); input.close(); };
process.on('SIGTERM', stop); process.on('SIGINT', stop);
try {
    let ready = false;
    for (let i = 0; i < 80; i++) { try { if ((await fetch(`${url}/login`)).ok) { ready = true; break; } } catch { /* Await isolated server. */ } await new Promise((r) => setTimeout(r, 250)); }
    assert(ready, 'Server QA belum siap.');
    console.log(JSON.stringify({ url, artifacts: path.relative(root, artifacts), number: setup.number, total: setup.total }));
    await new Promise((resolve) => { const timer = setTimeout(resolve, 600000); process.once('SIGINT', () => { clearTimeout(timer); resolve(); }); process.once('SIGTERM', () => { clearTimeout(timer); resolve(); }); });
} finally { stop(); }
