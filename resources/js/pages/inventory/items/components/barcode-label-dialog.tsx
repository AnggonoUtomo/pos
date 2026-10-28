import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Printer } from 'lucide-react';
import { useState } from 'react';
import { createPortal } from 'react-dom';
import { barcodeDrawing } from '../lib/barcode';
import type { ItemRow, UnitOption } from '../types';
import './barcode-label.css';

export function BarcodeLabelDialog({ item, units, onClose }: { item: ItemRow; units: UnitOption[]; onClose: () => void }) {
    const printable = item.units.filter((unit) => unit.barcode !== null && unit.barcode !== '');
    const [unitId, setUnitId] = useState(printable.find((unit) => unit.unit_id === item.base_unit_id)?.unit_id ?? printable[0]?.unit_id ?? '');
    const [copies, setCopies] = useState('1');
    const [width, setWidth] = useState('90');
    const [height, setHeight] = useState('40');
    const [paper, setPaper] = useState('a4');
    const selected = printable.find((unit) => unit.unit_id === unitId);
    const unitName = units.find((unit) => unit.id === unitId)?.name ?? '';
    const w = Number(width);
    const h = Number(height);
    const count = Number(copies);
    let drawing: ReturnType<typeof barcodeDrawing> | null = null;
    let error = '';
    try {
        if (!selected?.barcode) throw new Error('Belum ada barcode tersimpan untuk satuan ini.');
        if (!Number.isInteger(count) || count < 1 || count > 100) throw new Error('Jumlah label harus 1 sampai 100.');
        if (!Number.isInteger(w) || w < 40 || w > 190 || !Number.isInteger(h) || h < 35 || h > 80)
            throw new Error('Ukuran label: lebar 40-190 mm, tinggi 35-80 mm.');
        drawing = barcodeDrawing(selected.barcode);
        if (drawing.modules * 0.25 > w - 6) throw new Error('Barcode terlalu panjang. Perbesar lebar label; batang tidak dipaksa mengecil.');
    } catch (failure) {
        error = failure instanceof Error ? failure.message : 'Label belum siap.';
    }
    const label =
        drawing && !error ? (
            <div className="barcode-label" style={{ width: `${w}mm`, height: `${h}mm` }}>
                <strong className="barcode-label-name" title={item.name}>
                    {item.name}
                </strong>
                <span className="barcode-label-unit">{unitName}</span>
                <svg
                    role="img"
                    aria-label={`Barcode ${selected?.barcode}`}
                    viewBox={`0 0 ${drawing.modules} 50`}
                    style={{ width: `${drawing.modules * Math.min(0.4, (w - 6) / drawing.modules)}mm`, height: '14mm' }}
                    shapeRendering="crispEdges"
                >
                    <rect width={drawing.modules} height="50" fill="#fff" />
                    <path d={drawing.path} fill="#000" />
                </svg>
                <span className="barcode-label-code">{selected?.barcode}</span>
            </div>
        ) : null;
    const print = () => {
        if (!error && label) window.print();
    };
    return (
        <>
            <Dialog
                open
                onOpenChange={(open) => {
                    if (!open) onClose();
                }}
            >
                <DialogContent
                    className="max-h-[90dvh] overflow-y-auto sm:max-w-2xl"
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            print();
                        }
                    }}
                >
                    <DialogHeader>
                        <DialogTitle>Label barcode</DialogTitle>
                        <DialogDescription>{item.name}</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="barcode-unit">Satuan label</Label>
                            <Select value={unitId} onValueChange={setUnitId}>
                                <SelectTrigger id="barcode-unit">
                                    <SelectValue placeholder="Pilih satuan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {printable.map((unit) => (
                                        <SelectItem key={unit.unit_id} value={unit.unit_id}>
                                            {units.find((row) => row.id === unit.unit_id)?.name ?? unit.unit_id}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="barcode-paper">Kertas</Label>
                            <Select value={paper} onValueChange={setPaper}>
                                <SelectTrigger id="barcode-paper">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="a4">A4</SelectItem>
                                    <SelectItem value="label">Label tunggal</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        {[
                            { id: 'barcode-copies', title: 'Jumlah label', value: copies, change: setCopies, min: 1, max: 100 },
                            { id: 'barcode-width', title: 'Lebar label (mm)', value: width, change: setWidth, min: 40, max: 190 },
                            { id: 'barcode-height', title: 'Tinggi label (mm)', value: height, change: setHeight, min: 35, max: 80 },
                        ].map(({ id, title, value, change, min, max }) => (
                            <div className="space-y-2" key={id}>
                                <Label htmlFor={id}>{title}</Label>
                                <Input
                                    id={id}
                                    type="number"
                                    min={min}
                                    max={max}
                                    step="1"
                                    value={value}
                                    onChange={(event) => change(event.target.value)}
                                />
                            </div>
                        ))}
                    </div>
                    {error ? (
                        <p role="alert" className="text-destructive text-sm">
                            {error}
                        </p>
                    ) : (
                        <div className="overflow-auto border bg-white p-3" aria-label="Pratinjau label">
                            {label}
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={onClose}>
                            Tutup
                        </Button>
                        <Button disabled={Boolean(error)} onClick={print}>
                            <Printer />
                            Cetak label
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            {label &&
                createPortal(
                    <div
                        className={`barcode-print ${paper === 'label' ? 'barcode-print-single' : ''}`}
                        style={{ gridTemplateColumns: `repeat(${Math.max(1, Math.floor(192 / (w + 2)))}, ${w}mm)` }}
                    >
                        <style>{`@media print { @page { size: ${paper === 'a4' ? 'A4' : `${w}mm ${h}mm`}; margin: ${paper === 'a4' ? '10mm' : '0'}; } }`}</style>
                        {Array.from({ length: count }, (_, index) => (
                            <div className="barcode-print-cell" key={index}>
                                {label}
                            </div>
                        ))}
                    </div>,
                    document.body,
                )}
        </>
    );
}
