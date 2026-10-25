import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdir, writeFile, unlink } from 'node:fs/promises';
import { createServer } from 'node:net';
import path from 'node:path';
import { createInterface } from 'node:readline';
import { createRequire } from 'node:module';
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
        APP_CONFIG_CACHE: path.join(artifacts, 'config.php'), APP_ROUTES_CACHE: path.join(artifacts, 'routes.php'),
        DB_DATABASE: database, CACHE_STORE: 'array', SESSION_DRIVER: 'database', SESSION_COOKIE: `return_post_${id}`,
        SESSION_DOMAIN: 'null', SESSION_SECURE_COOKIE: 'false', MAIL_MAILER: 'array', BCRYPT_ROUNDS: '4' };
    const fixture = (input) => {
        const result = spawnSync('php', ['tests/Browser/mvp-checkout-fixture.php'],
            { cwd: root, env, input: JSON.stringify(input), encoding: 'utf8', windowsHide: true });
        assert.equal(result.status, 0, 'Fixture QA gagal.');
        return JSON.parse(result.stdout);
    };
    const password = randomBytes(32).toString('hex');
    const formOnly = process.argv.includes('--operational-form-only');
    const sidebarUx = process.argv.includes('--sidebar-ux-only');
    const uiRefresh = process.argv.includes('--ui-refresh-only');
    const dashboardOnly = uiRefresh || process.argv.includes('--dashboard-only');
    const manualBackup = process.argv.includes('--manual-backup-only');
    const operational = manualBackup || sidebarUx || formOnly || process.argv.includes('--navigation-only') || process.argv.includes('--operational-only') || process.argv.includes('--operational-pdf-only');
    const setup = fixture({ password, operational_only: operational, dashboard_only: dashboardOnly, manual_backup: manualBackup });
    assert.equal(setup.status, 'READY');
    console.log('PASS: fixture MVP disposable siap');
    server = spawn('php', ['-S', `127.0.0.1:${port}`, path.join(root, manualBackup ? 'tests/Browser/manual-backup-router.php' : 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
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
        if (uiRefresh) {
            await tool('resize_page', { pageId, width: 1440, height: 1000 });
            await locate(pageId, 'textbox', 'Email address');
            await tool('take_screenshot', { pageId, filePath: path.join(artifacts, 'login.png') });
        }
        await tool('fill_form', { pageId, elements: [
            { uid: await locate(pageId, 'textbox', 'Email address'), value: `qa-${role}@example.test` },
            { uid: await locate(pageId, 'textbox', 'Password'), value: password },
        ] });
        await click(pageId, 'button', 'Log in');
        await locate(pageId, 'heading', 'Dasbor Operasional');
        if (!dashboardOnly) await tool('navigate_page', { pageId, url: `${url}${operational ? '/platform/operational-settings' : '/platform/company-settings'}` });
        return pageId;
    };
    const pageId = await login('manager');

    if (uiRefresh) {
        const rootResponse = await fetch(`${url}/`, { redirect: 'manual' });
        assert.equal(rootResponse.status, 302);
        assert.equal(rootResponse.headers.get('location'), `${url}/login`);
        const noOverflow = async () => assert.equal(await inspect(pageId,
            '() => document.documentElement.scrollWidth > innerWidth'), false, 'Workspace overflow');
        const capture = name => tool('take_screenshot', { pageId, filePath: path.join(artifacts, `${name}.png`) });
        const chooseMode = async mode => {
            await click(pageId, 'button', 'Tema');
            await click(pageId, 'menuitem', mode);
        };
        const surfaces = [];
        for (const mode of ['Light', 'Dark']) {
            await chooseMode(mode);
            await noOverflow();
            const colors = await inspect(pageId, `() => {
                const color = (selector) => getComputedStyle(document.querySelector(selector)).backgroundColor;
                return {dark:document.documentElement.classList.contains('dark'),
                    metrics:Array.from(document.querySelectorAll('.dashboard-metric'), el=>getComputedStyle(el).backgroundColor),
                    header:color('header[data-scrolled]'), sidebar:color('[data-sidebar="sidebar"]')};
            }`);
            assert.equal(colors.dark, mode === 'Dark');
            assert.equal(new Set(colors.metrics).size, 4, 'Surface metric harus berbeda');
            assert((await snapshot(pageId)).includes(setup.invoice_number), 'Invoice nyata hilang');
            surfaces.push({mode,...colors});
            await capture(`desktop-${mode.toLowerCase()}`);
        }
        await tool('press_key', {pageId,key:'Control+k'});
        await locate(pageId,'dialog','Command Palette');
        await tool('press_key',{pageId,key:'Escape'});
        await new Promise(resolve=>setTimeout(resolve,250));
        await click(pageId,'button','Shortcut');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'shortcut-dropdown.png')});
        const shortcut=await inspect(pageId,`() => ({expanded:Array.from(document.querySelectorAll('button')).find(el=>el.textContent.includes('Shortcut'))?.getAttribute('aria-expanded'),
            menu:document.querySelector('[role=menu]')?.textContent})`);
        assert(shortcut.expanded==='true' && shortcut.menu?.includes('Shortcut Halaman'),JSON.stringify(shortcut));
        await tool('press_key',{pageId,key:'Escape'});
        await new Promise(resolve=>setTimeout(resolve,250));
        await click(pageId,'button','Tema');
        await click(pageId,'button','Forest');
        await tool('press_key',{pageId,key:'Escape'});
        await tool('navigate_page',{pageId,url:`${url}/inventory/items`});
        await locate(pageId,'heading','Barang');
        assert.equal(await inspect(pageId,'() => document.documentElement.dataset.theme'),'forest','Accent tidak dipertahankan');
        await click(pageId,'button','Tambah barang');
        await locate(pageId,'dialog','Tambah barang');
        await noOverflow();
        await capture('desktop-master-dialog');
        await click(pageId,'button','Batal');
        await click(pageId,'button','Tema');
        await click(pageId,'button','Default');
        await tool('press_key',{pageId,key:'Escape'});
        await tool('navigate_page',{pageId,url:`${url}/dashboard`});
        await locate(pageId,'heading','Dasbor Operasional');
        await tool('resize_page',{pageId,width:1024,height:768});
        await chooseMode('Dark');
        await noOverflow();
        await capture('tablet-expanded');
        await click(pageId,'button','Toggle Sidebar');
        const before=await inspect(pageId,`() => {
            const el=document.querySelector('[data-sidebar="content"]'); el.scrollTop=el.scrollHeight;
            return {top:el.scrollTop,origin:performance.timeOrigin};
        }`);
        assert(before.top>0,'Sidebar harus dapat discroll');
        await click(pageId,'link','Laporan Operasional');
        await locate(pageId,'heading','Laporan operasional');
        const after=await inspect(pageId,`() => ({top:document.querySelector('[data-sidebar="content"]').scrollTop,origin:performance.timeOrigin})`);
        assert(Math.abs(before.top-after.top)<2 && before.origin===after.origin,'Scroll sidebar tidak dipertahankan');
        await inspect(pageId,'() => {document.querySelector("[data-sidebar=content]").scrollTop=0; return true}');
        await new Promise(resolve=>setTimeout(resolve,350));
        await tool('hover',{pageId,uid:await locate(pageId,'link','Gudang')});
        await new Promise(resolve=>setTimeout(resolve,500));
        await capture('tablet-tooltip');
        const tooltip=await inspect(pageId,`() => ({state:document.querySelector('[data-collapsible]').dataset.state,
            tips:Array.from(document.querySelectorAll('[role=tooltip]'),el=>({text:el.textContent,hidden:el.hidden}))})`);
        assert(tooltip.state==='collapsed' && tooltip.tips.some(tip=>tip.text==='Gudang' && !tip.hidden),JSON.stringify(tooltip));
        const requests=await tool('list_network_requests',{pageId,resourceTypes:['xhr','fetch','prefetch']});
        assert(!requests.includes(`${url}/inventory/warehouses`),'Hover memicu request');
        const bounds=await inspect(pageId,`() => ({sidebar:document.querySelector('[data-sidebar="sidebar"]').getBoundingClientRect().right,
            header:document.querySelector('header[data-scrolled]').getBoundingClientRect().left})`);
        assert(bounds.header>=bounds.sidebar,'Header menutupi sidebar');
        await capture('tablet-collapsed');
        await inspect(pageId,'() => {window.scrollTo(0,180); return true}');
        await new Promise(resolve=>setTimeout(resolve,200));
        assert.equal(await inspect(pageId,'() => document.querySelector("header[data-scrolled]").dataset.scrolled'),'true','Header glass tidak aktif');
        await capture('tablet-glass');
        await tool('navigate_page',{pageId,url:`${url}/pos`});
        await locate(pageId,'heading','POS');
        for(const [label,value] of [['Gudang','Gudang Utama'],['Pelanggan','Installer CCTV Demo']]) {
            await click(pageId,'combobox',label);
            await click(pageId,'option',value);
        }
        await click(pageId,'button','Tambah Kamera IP 2MP');
        await click(pageId,'button','Periksa keranjang');
        await locate(pageId,'button','Pembayaran');
        await click(pageId,'button','Pembayaran');
        await locate(pageId,'dialog','Pembayaran');
        await locate(pageId,'combobox','Metode 1');
        await noOverflow();
        await capture('tablet-pos-payment');
        await click(pageId,'button','Batal');
        await until(pageId,value=>!value.includes('Keranjang berhasil diperiksa.'),'toast selesai');
        await capture('tablet-pos');
        await tool('resize_page',{pageId,width:1440,height:1000});
        await chooseMode('Light');
        await noOverflow();
        await capture('desktop-pos-light');
        const errors=await tool('list_console_messages',{pageId,types:['error']});
        assert(errors.includes('<no console messages found>'),'Console error UI');
        const state=fixture({verify_dashboard:true,fingerprint:setup.fingerprint});
        const report={status:'PASS',checks:['root/login','light/dark metric surfaces','command palette/shortcut','accent persistence','master dialog',
            'sidebar scroll/tooltip/no hover request','tablet expanded/collapsed no overlap','glass header',
            'POS quote/payment dialog','no business mutations/clean console'],
            surfaces,state,artifacts:path.relative(root,artifacts)};
        await writeFile(path.join(artifacts,'ui-refresh-report.json'),JSON.stringify(report,null,2));
        console.log(JSON.stringify(report));
    } else if (manualBackup) {
        await tool('resize_page',{pageId,width:1440,height:900});
        await click(pageId,'button','Backup sekarang');
        await locate(pageId,'dialog','Backup database sekarang?');
        await click(pageId,'button','Batal');
        await tool('press_key',{pageId,key:'Alt+b'});
        await locate(pageId,'dialog','Backup database sekarang?');
        await click(pageId,'button','Buat backup');
        await until(pageId,current=>current.includes('pos-20261025-220000-1234abcd.sql'),'backup result');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'desktop-manual-backup.png')});
        await tool('resize_page',{pageId,width:1024,height:768});
        assert.equal(await inspect(pageId,'() => document.documentElement.scrollWidth > innerWidth'),false,'Overflow backup tablet');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'tablet-manual-backup.png')});
        const readonly = await login('readonly');
        assert(!(await snapshot(readonly)).includes('button "Backup sekarang"'),'Tombol backup terbuka ke viewer');
        for(const current of [pageId,readonly]) {
            const errors=await tool('list_console_messages',{pageId:current,types:['error']});
            assert(errors.includes('<no console messages found>'),'Console error backup');
        }
        const state=fixture({verify_manual_backup:true});
        console.log(JSON.stringify({status:'PASS',checks:['confirmation/cancel/shortcut','manual result and audit','view-only guard','desktop/tablet and clean console'],state,artifacts:path.relative(root,artifacts)}));
    } else if (dashboardOnly) {
        const money = value => {
            const [whole, fraction=''] = value.split('.');
            const tail = fraction.replace(/0+$/, '');
            return `Rp ${new Intl.NumberFormat('id-ID').format(BigInt(whole))}${tail ? ','+tail : ''}`;
        };
        const checkMetrics = async expected => {
            const current = await inspect(pageId, `() => Array.from(document.querySelectorAll('[aria-label="Ringkasan hari ini"] h2'), el => el.parentElement.parentElement.querySelector('p').textContent)`);
            assert.deepEqual(current, [expected.sales.total, expected.purchases.total_value, expected.payments.incoming, expected.payments.outgoing].map(money));
        };
        await tool('resize_page',{pageId,width:1440,height:900});
        await checkMetrics(setup.report);
        let current = await snapshot(pageId);
        assert(current.includes(setup.invoice_number) && current.includes(money(setup.report.stock.total_value)), 'Data invoice/stok nyata tidak tampil');
        assert(!/Realtime mock|Shift aktif|18\.420\.000|FIFO layer sehat|24 antre|Baseline Yang Dijaga/.test(current), 'Placeholder dashboard masih tampil');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'desktop-dashboard.png')});
        await click(pageId,'link',setup.invoice_number);
        await locate(pageId,'heading','Riwayat penjualan');
        await click(pageId,'button',`Lihat ${setup.invoice_number}`);
        await locate(pageId,'dialog','Bukti penjualan');
        await until(pageId, value=>value.includes('100,00') || value.includes('Rp 100'), 'receipt snapshot');
        await click(pageId,'button','Selesai');
        await tool('navigate_page',{pageId,url:`${url}/dashboard`});
        await locate(pageId,'heading','Dasbor Operasional');
        const before = await inspect(pageId,'() => performance.timeOrigin');
        const changed = fixture({dashboard_add_sale:true});
        await click(pageId,'button','Muat ulang ringkasan');
        await until(pageId, value=>value.includes(`${changed.report.sales.count} nota lunas`), 'refreshed data');
        await checkMetrics(changed.report);
        await tool('press_key',{pageId,key:'Alt+r'});
        await locate(pageId,'button','Muat ulang ringkasan');
        assert.equal(await inspect(pageId,'() => performance.timeOrigin'),before,'Refresh memuat ulang dokumen penuh');
        await tool('resize_page',{pageId,width:1024,height:768});
        assert.equal(await inspect(pageId,'() => document.documentElement.scrollWidth > innerWidth'),false,'Dashboard overflow tablet');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'tablet-dashboard.png')});
        for (const role of ['cashier','empty-cashier','limited']) {
            const scoped = await login(role);
            const view = await snapshot(scoped);
            assert(!view.includes('heading "Penjualan hari ini"'), 'Ringkasan toko bocor ke user tanpa izin');
            if(role==='cashier') {
                assert(view.includes(setup.invoice_number) && view.includes('Penjualan terbaru milik Anda'), 'Scope kasir keliru');
                assert.equal(await inspect(scoped,'() => document.querySelectorAll("tbody tr").length'),1);
            } else if(role==='empty-cashier') {
                assert.equal(await inspect(scoped,'() => document.querySelector("tbody").textContent'),'Belum ada penjualan milik Anda.','Empty state hilang');
                await tool('take_screenshot',{pageId:scoped,filePath:path.join(artifacts,'empty-dashboard.png')});
            }
            else assert(!view.includes('heading "Penjualan terbaru"'),'Invoice bocor ke user terbatas');
            const errors = await tool('list_console_messages',{pageId:scoped,types:['error']});
            assert(errors.includes('<no console messages found>'),'Console error user terbatas');
        }
        const errors=await tool('list_console_messages',{pageId,types:['error']});
        assert(errors.includes('<no console messages found>'),'Console error dashboard');
        const state=fixture({verify_dashboard:true,fingerprint:changed.fingerprint});
        const report={status:'PASS',checks:['real metrics and stock','manual/shortcut refresh without document reload','invoice history/receipt','desktop/tablet no overflow','cashier/limited/empty states','readonly and clean console'],state,artifacts:path.relative(root,artifacts)};
        await writeFile(path.join(artifacts,'dashboard-report.json'),JSON.stringify(report,null,2));
        console.log(JSON.stringify(report));
    } else if (sidebarUx) {
        await tool('resize_page',{pageId,width:1440,height:800});
        await click(pageId,'combobox','Format kertas');
        await click(pageId,'option','58 mm');
        const initial=await inspect(pageId,'() => ({origin:performance.timeOrigin,paper:document.querySelector("#receipt-paper").textContent})');
        await tool('hover',{pageId,uid:await locate(pageId,'link','Gudang')});
        await new Promise(resolve=>setTimeout(resolve,700));
        const network=await tool('list_network_requests',{pageId,resourceTypes:['xhr','fetch','prefetch']});
        const hovered=await inspect(pageId,'() => ({origin:performance.timeOrigin,paper:document.querySelector("#receipt-paper").textContent})');
        assert.deepEqual(hovered,initial,'Hover mengubah dokumen atau draft aktif');
        const cases=[];
        for (const collapsed of [false,true]) {
            if(collapsed) {
                await tool('navigate_page',{pageId,url:`${url}/platform/operational-settings`});
                await tool('resize_page',{pageId,width:1024,height:600});
                await click(pageId,'button','Toggle Sidebar');
            }
            const before=await inspect(pageId,`() => {
                const el=document.querySelector('[data-sidebar="content"]');
                el.scrollTop=el.scrollHeight; window.scrollTo(0,100);
                return {top:el.scrollTop,origin:performance.timeOrigin,body:window.scrollY};
            }`);
            assert(before.top>0,'Fixture sidebar harus dapat discroll');
            await click(pageId,'link','Laporan Operasional');
            await locate(pageId,'heading','Laporan operasional');
            const after=await inspect(pageId,`() => ({top:document.querySelector('[data-sidebar="content"]').scrollTop,origin:performance.timeOrigin,body:window.scrollY})`);
            cases.push({collapsed,before,after});
            await tool('take_screenshot',{pageId,filePath:path.join(artifacts,collapsed?'tablet-sidebar-scroll.png':'desktop-sidebar-scroll.png')});
        }
        const observed={hoverPrefetch:network.includes(`${url}/inventory/warehouses`),cases};
        await writeFile(path.join(artifacts,'sidebar-observed.json'),JSON.stringify(observed,null,2));
        assert(!observed.hoverPrefetch && cases.every(({before,after})=>Math.abs(before.top-after.top)<2 && before.origin===after.origin && after.body===0),
            'Hover/scroll sidebar belum sesuai: '+JSON.stringify(observed));
        const errors=await tool('list_console_messages',{pageId,types:['error']});
        assert(errors.includes('<no console messages found>'),'Console errors');
        const state=fixture({verify_navigation:true});
        console.log(JSON.stringify({status:'PASS',checks:['hover without request/reload/draft reset','sidebar scroll retained expanded/collapsed','new workspace scroll independent','no business mutations','clean console'],state,artifacts:path.relative(root,artifacts)}));
    } else if (process.argv.includes('--operational-pdf-only')) {
        assert(process.env.QA_PLAYWRIGHT_MODULE,'Gunakan Playwright existing hanya untuk print-to-PDF.');
        const {chromium}=createRequire(import.meta.url)(process.env.QA_PLAYWRIGHT_MODULE);
        const browser=await chromium.launch({channel:'chrome',headless:true});
        try {
            const page=await browser.newPage({viewport:{width:1440,height:1000}});
            await page.goto(`${url}/login`);
            await page.getByLabel('Email address').fill('qa-manager@example.test');
            await page.getByLabel('Password',{exact:true}).fill(password);
            await page.getByRole('button',{name:'Log in',exact:true}).click();
            await page.getByRole('heading',{name:'Dasbor Operasional',exact:true}).waitFor();
            await page.goto(`${url}/platform/operational-settings`);
            for(const [label,paper,width] of [['58 mm','58mm',58],['80 mm','80mm',80],['A4','a4',210]]) {
                await page.getByRole('combobox',{name:'Format kertas'}).click();
                await page.getByRole('option',{name:label,exact:true}).click();
                await page.getByRole('button',{name:'Uji cetak',exact:true}).click();
                await page.locator(`.pos-receipt-print[data-paper="${paper}"]`).waitFor({state:'attached'});
                await page.emulateMedia({media:'print'});
                assert(await page.locator('.pos-receipt-print').isVisible());
                assert(!(await page.locator('#app').isVisible()),'Workspace harus tersembunyi saat print');
                const overflow=await page.locator('.pos-receipt-print .receipt-paper').evaluate(el=>el.scrollWidth>el.clientWidth);
                assert(!overflow,'Receipt print overflow');
                const pdf=await page.pdf({path:path.join(artifacts,`print-${paper}.pdf`),
                    width:`${width}mm`,height:paper==='a4'?'297mm':'200mm',printBackground:true,preferCSSPageSize:true});
                assert(pdf.length>1000 && pdf.subarray(0,5).toString()==='%PDF-','PDF kosong/tidak valid');
                const media=pdf.toString('latin1').match(/\/MediaBox\s*\[0 0 ([\d.]+) ([\d.]+)\]/);
                assert(media && Math.abs(Number(media[1])-width*72/25.4)<1,'Lebar PDF tidak sesuai kertas');
                await page.emulateMedia({media:'screen'});
                await page.getByRole('button',{name:'Tutup',exact:true}).click();
            }
            console.log(JSON.stringify({status:'PASS',checks:['58mm/80mm/A4 PDF dimensions','print-only portal','no text overflow'],artifacts:path.relative(root,artifacts)}));
        } finally { await browser.close(); }
    } else if (formOnly || process.argv.includes('--operational-only')) {
        await tool('navigate_page', {pageId, url: `${url}/platform/operational-settings`});
        await tool('resize_page', {pageId, width:1440, height:1000});
        await locate(pageId, 'heading', 'Pengaturan operasional');
        const choosePaper = async (label) => { await click(pageId, 'combobox', 'Format kertas'); await click(pageId, 'option', label); };
        const unlocked=await inspect(pageId,`() => ({paperDisabled:document.querySelector('#receipt-paper').matches(':disabled'),timeDisabled:document.querySelector('#backup-time').matches(':disabled'),backupDisabled:document.querySelector('#backup-enabled').matches(':disabled'),enabled:document.querySelector('#backup-enabled').getAttribute('aria-checked')})`);
        assert.deepEqual(unlocked,{paperDisabled:false,timeDisabled:false,backupDisabled:false,enabled:'false'},'Form harus dapat disiapkan sebelum backup aktif');
        await writeFile(path.join(artifacts,'operational-form-snapshot.txt'),await snapshot(pageId));
        await tool('fill_form', {pageId, elements:[{uid:await locate(pageId,'InputTime','Jam backup (WIB)'),value:'22:15'}]});
        // Puppeteer's time-input fill does not notify React; use native keyboard events.
        await click(pageId,'spinbutton','Hours Hours');
        await tool('press_key',{pageId,key:'ArrowUp'});
        await tool('press_key',{pageId,key:'ArrowDown'});
        await click(pageId, 'button', 'Simpan pengaturan');
        await until(pageId, value => value.includes('Pengaturan operasional tersimpan.'), 'operational save');
        await tool('navigate_page', {pageId, url: `${url}/platform/operational-settings`});
        await locate(pageId,'heading','Pengaturan operasional');
        const prepared=await inspect(pageId,`() => ({time:document.querySelector('#backup-time').value,enabled:document.querySelector('#backup-enabled').getAttribute('aria-checked')})`);
        assert.deepEqual(prepared,{time:'22:15',enabled:'false'},'Persiapan jam tidak boleh mengaktifkan backup');
        await choosePaper('58 mm');
        await click(pageId,'checkbox','Backup otomatis harian');
        await click(pageId,'button','Simpan pengaturan');
        await until(pageId,value=>value.includes('Jadwal tersimpan 22:15 WIB'),'enabled schedule');
        const persisted=await inspect(pageId,`() => ({paper:document.querySelector('#receipt-paper')?.textContent,time:document.querySelector('#backup-time')?.value,enabled:document.querySelector('#backup-enabled')?.getAttribute('aria-checked')})`);
        assert.deepEqual(persisted,{paper:'58 mm',time:'22:15',enabled:'true'},'Preferensi tidak tersimpan: '+JSON.stringify(persisted));
        await tool('take_screenshot', {pageId,filePath:path.join(artifacts,'desktop-operational.png')});
        for (const [label, paper, millimeters] of (formOnly ? [] : [['58 mm','58mm',54],['80 mm','80mm',76],['A4','a4',null]])) {
            await choosePaper(label);
            await click(pageId, 'button', 'Uji cetak');
            await locate(pageId, 'dialog', 'Uji cetak');
            const frame = await inspect(pageId, `() => {
                const receipt=document.querySelector('.pos-receipt-print');
                const sample=document.querySelector('[role="dialog"] .receipt-paper');
                return {paper:receipt?.dataset.paper,width:sample?.getBoundingClientRect().width,
                    overflow:sample.scrollWidth > sample.clientWidth, bodyOverflow:document.documentElement.scrollWidth>innerWidth,
                    sample:sample.textContent.includes('Bukan bukti transaksi')};
            }`);
            assert.equal(frame.paper,paper); assert(frame.sample && !frame.overflow && !frame.bodyOverflow);
            if (millimeters) assert(Math.abs(frame.width - millimeters * 96 / 25.4) < 2, 'Lebar receipt tidak sesuai');
            await tool('take_screenshot',{pageId,filePath:path.join(artifacts,`preview-${paper}.png`)});
            await click(pageId, 'button', 'Tutup');
        }
        await choosePaper('80 mm');
        await click(pageId, 'checkbox', 'Backup otomatis harian');
        await click(pageId, 'button', 'Simpan pengaturan');
        await until(pageId, value => value.includes('Backup otomatis nonaktif'), 'backup disabled');
        await tool('resize_page',{pageId,width:1024,height:768});
        const frame = await inspect(pageId,'() => ({overflow:document.documentElement.scrollWidth>innerWidth})');
        assert(!frame.overflow,'Overflow pengaturan tablet');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'tablet-operational.png')});
        const errors=await tool('list_console_messages',{pageId,types:['error']});
        assert(errors.includes('<no console messages found>'),'Console errors');
        const state=fixture({verify_operational:true});
        const report={status:'PASS',checks:['super-system editable without explicit grants','edit/save time while backup disabled','save/reload paper+optional schedule',...(formOnly?[]:['58/80/A4 preview widths']),'no business mutations','desktop/tablet','clean console'],state,artifacts:path.relative(root,artifacts)};
        await writeFile(path.join(artifacts,'operational-report.json'),JSON.stringify(report,null,2));
        console.log(JSON.stringify(report));
    } else if (process.argv.includes('--navigation-only')) {
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
        const navigation=await snapshot(pageId);
        assert(!navigation.includes('button "Harga"') && !navigation.includes('link "Harga"'), 'Placeholder Harga masih ada di sidebar');
        await tool('take_screenshot', {pageId,filePath:path.join(artifacts,'tablet-navigation.png')});
        await tool('press_key',{pageId,key:'Control+k'});
        await locate(pageId,'dialog','Command Palette');
        const palette=await snapshot(pageId);
        assert(!palette.includes('Harga level pelanggan dan multi satuan'), 'Placeholder Harga masih ada di command palette');
        await tool('take_screenshot',{pageId,filePath:path.join(artifacts,'tablet-command-palette.png')});
        await tool('press_key',{pageId,key:'Escape'});
        const errors=await tool('list_console_messages',{pageId,types:['error']});
        assert(errors.includes('<no console messages found>'),'Console errors');
        console.log(JSON.stringify({status:'PASS',checks:['tablet navigation label/badge nonoverlap','no Harga placeholder in sidebar/palette','command palette opens normally','clean console'],artifacts:path.relative(root,artifacts)}));
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
