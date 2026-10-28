import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const source = readFileSync('resources/js/pages/sales/pos/lib/workspace.ts', 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext } }).outputText;
const { initialPosContext, workspaceView } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);
const main = { id: 'main', name: 'Gudang Utama Latihan' };
const retail = { id: 'retail', levelName: 'Eceran' };
assert.deepEqual(initialPosContext([main, { id: 'other', name: 'Cadangan' }], [retail, { id: 'trade', levelName: 'Grosir' }]), {
    warehouseId: 'main',
    customerId: 'retail',
});
assert.deepEqual(initialPosContext([], []), { warehouseId: '', customerId: '' });
assert.deepEqual(initialPosContext([main, { id: 'duplicate', name: 'Gudang Utama Kedua' }], [retail, retail]), { warehouseId: '', customerId: '' });
assert.deepEqual(initialPosContext([{ id: 'other', name: 'Bukan Gudang Utama' }], [{ id: 'trade', levelName: 'Grosir' }]), {
    warehouseId: '',
    customerId: '',
});
assert.equal(workspaceView('classic'), 'classic');
assert.equal(workspaceView('visual'), 'visual');
assert.equal(workspaceView(null), 'visual');
assert.equal(workspaceView('invalid'), 'visual');
console.log('PASS: default unik/tidak tersedia/ambigu dan preferensi tampilan.');
