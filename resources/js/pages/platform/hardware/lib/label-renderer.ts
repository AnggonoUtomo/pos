import { barcodeDrawing } from '../../../../lib/barcode';
import type { LabelSnapshot, LabelSpecification } from '../types';

export function labelLayout(label: LabelSnapshot, specification: LabelSpecification) {
    const { width_mm: width, height_mm: height, dpi, orientation } = specification;
    if (!Number.isInteger(width) || width < 20 || width > 100 || !Number.isInteger(height) || height < 20 || height > 150 || dpi !== 203 || !['portrait', 'landscape'].includes(orientation)) {
        throw new Error('Ukuran label harus 20-100 x 20-150 mm, dengan resolusi 203 DPI.');
    }
    const drawing = barcodeDrawing(label.barcode);
    const widthPx = Math.round((width * dpi) / 25.4);
    const heightPx = Math.round((height * dpi) / 25.4);
    const moduleWidth = Math.floor((widthPx - 24) / drawing.modules);
    if (moduleWidth < 2) throw new Error('Barcode terlalu panjang untuk label ini. Pilih ukuran lebih lebar.');
    return { ...drawing, widthPx, heightPx, moduleWidth, barcodeHeight: Math.max(48, Math.min(96, Math.floor(heightPx * 0.37))) };
}

export function renderLabelPNG(label: LabelSnapshot, specification: LabelSpecification): { base64: string; dataUrl: string } {
    const layout = labelLayout(label, specification);
    const canvas = document.createElement('canvas');
    canvas.width = layout.widthPx;
    canvas.height = layout.heightPx;
    const context = canvas.getContext('2d');
    if (!context) throw new Error('Pratinjau label belum tersedia pada browser ini.');
    context.imageSmoothingEnabled = false;
    context.fillStyle = '#fff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = '#000';
    context.textAlign = 'center';
    context.font = 'bold 18px sans-serif';
    const maxWidth = canvas.width - 24;
    const words = label.name.split(/\s+/);
    const lines: string[] = [];
    let line = '';
    for (const word of words) {
        if (context.measureText(word).width > maxWidth) throw new Error('Nama barang tidak muat pada label. Pilih ukuran lebih lebar.');
        const next = line ? `${line} ${word}` : word;
        if (context.measureText(next).width > maxWidth) { lines.push(line); line = word; }
        else line = next;
    }
    if (line) lines.push(line);
    if (lines.length > 2) throw new Error('Nama barang terlalu panjang untuk label. Pilih ukuran lebih lebar.');
    const barcodeY = 14 + lines.length * 21 + 36;
    if (barcodeY + layout.barcodeHeight + 26 > canvas.height) throw new Error('Isi label tidak muat. Perbesar tinggi label.');
    lines.forEach((text, index) => context.fillText(text, canvas.width / 2, 25 + index * 21));
    context.font = '14px sans-serif';
    for (const [text, y] of [[label.sku, barcodeY - 23], [label.unit_name, barcodeY - 6]] as const) {
        if (context.measureText(text).width > maxWidth) throw new Error('SKU atau satuan tidak muat pada label.');
        context.fillText(text, canvas.width / 2, y);
    }
    const x = Math.floor((canvas.width - layout.modules * layout.moduleWidth) / 2) + 10 * layout.moduleWidth;
    [...layout.bars].forEach((bar, index) => {
        if (bar === '1') context.fillRect(x + index * layout.moduleWidth, barcodeY, layout.moduleWidth, layout.barcodeHeight);
    });
    context.font = '14px monospace';
    if (context.measureText(label.barcode).width > maxWidth) throw new Error('Teks barcode tidak muat pada label.');
    context.fillText(label.barcode, canvas.width / 2, barcodeY + layout.barcodeHeight + 18);
    const dataUrl = canvas.toDataURL('image/png');
    return { base64: dataUrl.slice('data:image/png;base64,'.length), dataUrl };
}
