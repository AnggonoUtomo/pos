import JsBarcode from 'jsbarcode';

export function internalBarcode(): string {
    return [...crypto.getRandomValues(new Uint32Array(2))].map((part) => String(part).padStart(10, '0')).join('');
}

export function barcodeDrawing(code: string): { bars: string; modules: number; path: string } {
    if (!/^[\x20-\x7e]{1,64}$/.test(code)) throw new Error('Label memerlukan barcode ASCII tercetak, maksimal 64 karakter.');
    const target: { encodings?: { data: string }[] } = {};
    JsBarcode(target, code, { format: 'CODE128', displayValue: false });
    const bars = target.encodings?.map((encoding) => encoding.data).join('');
    if (!bars || !/^[01]+$/.test(bars)) throw new Error('Barcode tidak dapat dibuat.');
    // Keep ten modules of quiet zone on each side; encoding remains library-owned.
    const path = [...bars.matchAll(/1+/g)].map((match) => `M${match.index + 10} 0h${match[0].length}v50h-${match[0].length}z`).join('');
    return { bars, modules: bars.length + 20, path };
}
