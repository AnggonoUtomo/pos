import assert from 'node:assert/strict';
import { registerHooks } from 'node:module';
registerHooks({ resolve(specifier, context, next) {
    try { return next(specifier, context); } catch (error) {
        if (error.code === 'ERR_MODULE_NOT_FOUND' && specifier.startsWith('.') && !/\.[a-z]+$/i.test(specifier)) return next(`${specifier}.ts`, context);
        throw error;
    }
} });
const { labelLayout } = await import('../../resources/js/pages/platform/hardware/lib/label-renderer.ts');
const { printConfiguration, createQzGateway } = await import('../../resources/js/pages/platform/hardware/gateway/qz-gateway.ts');
const label = { name: 'Kamera CCTV', sku: 'DEMO-CAMERA', unit_name: 'Unit', barcode: '001234567890' };
const profile = { width_mm: 50, height_mm: 30, dpi: 203, orientation: 'portrait' };
const layout = labelLayout(label, profile);
assert.equal(layout.widthPx, 400);
assert.equal(layout.heightPx, 240);
assert(Number.isInteger(layout.moduleWidth) && layout.moduleWidth >= 2);
assert.equal(layout.modules, layout.bars.length + 20);
for (const invalid of [{ width_mm: 101 }, { height_mm: 19 }, { dpi: 300 }, { orientation: 'raw' }]) assert.throws(() => labelLayout(label, { ...profile, ...invalid }));
assert.throws(() => labelLayout({ ...label, barcode: 'A'.repeat(64) }, profile));
assert.throws(() => labelLayout({ ...label, barcode: 'AA\nBB' }, profile));
const config = printConfiguration(profile, 2, 'test-job');
assert.equal(config.copies, 2);
assert.equal(config.units, 'mm');
assert.equal(config.interpolation, 'nearest-neighbor');
assert.equal(config.density, 203 / 25.4);
assert.throws(() => printConfiguration(profile, 101, 'bad'));
const gateway = createQzGateway(['localhost']);
await assert.rejects(() => gateway.connect('evil.example'));
assert.equal(gateway.isConnected(), false);
await gateway.disconnect();
console.log('PASS: integer-dot Code128 layout, quiet zones, size/copies, metric density and host restriction; no hardware IO.');
