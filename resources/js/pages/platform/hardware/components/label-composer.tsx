import { Autocomplete } from '@/components/shared/autocomplete';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Image, Printer } from 'lucide-react';
import { useEffect, useState } from 'react';
import { hardwareRequest } from '../hooks/use-label-print';
import { renderLabelPNG } from '../lib/label-renderer';
import type { CatalogLabel, PrinterProfile } from '../types';

export function LabelComposer({ profiles, profileId, setProfileId, selected, onSelected, canPrint, printingEnabled, busy, onPrint }: {
    profiles: PrinterProfile[]; profileId: string; setProfileId: (id: string) => void;
    selected: CatalogLabel | null; onSelected: (label: CatalogLabel) => void; canPrint: boolean;
    printingEnabled: boolean; busy: boolean; onPrint: (label: CatalogLabel, profile: PrinterProfile, copies: number) => void;
}) {
    const [query, setQuery] = useState('');
    const [rows, setRows] = useState<CatalogLabel[]>([]);
    const [copies, setCopies] = useState('1');
    const [searchError, setSearchError] = useState('');
    const [preview, setPreview] = useState('');
    const [previewError, setPreviewError] = useState('');
    const profile = profiles.find((row) => row.id === profileId && row.is_active && !row.deleted_at);
    useEffect(() => {
        if (!canPrint) return;
        let active = true;
        const timer = setTimeout(() => {
            void hardwareRequest<{ data: CatalogLabel[] }>(`/platform/hardware/catalog?${new URLSearchParams({ q: query, page: '1' })}`)
                .then((value) => { if (active) { setRows(value.data); setSearchError(''); } })
                .catch(() => { if (active) { setRows([]); setSearchError('Katalog label belum dapat dimuat.'); } });
        }, 250);
        return () => { active = false; clearTimeout(timer); };
    }, [query, canPrint]);
    useEffect(() => {
        setPreview(''); setPreviewError('');
        if (selected && profile) {
            try { setPreview(renderLabelPNG(selected, profile).dataUrl); }
            catch (error) { setPreviewError(error instanceof Error ? error.message : 'Pratinjau belum dapat dibuat.'); }
        }
    }, [selected, profile]);
    const options = [...new Map([...(selected ? [selected] : []), ...rows].map((row) => [`${row.item_id}:${row.unit_id}`, row])).values()];
    return <section className="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(280px,1fr)]">
        <div className="min-w-0 space-y-5">
            <h2 className="text-base font-semibold">Label barang</h2>
            <div className="space-y-2"><Label htmlFor="label-item">Barang / satuan / barcode</Label>
                <Autocomplete id="label-item" value={selected ? `${selected.item_id}:${selected.unit_id}` : ''} disabled={!canPrint || busy}
                    onSearchChange={setQuery} onValueChange={(value) => { const row = options.find((item) => `${item.item_id}:${item.unit_id}` === value); if (row) onSelected(row); }}
                    options={options.map((row) => ({ value: `${row.item_id}:${row.unit_id}`, label: `${row.name} - ${row.unit_name} - ${row.barcode}`, searchText: row.sku }))}
                    placeholder="Cari barang, SKU atau barcode" />
                {searchError && <p className="text-destructive text-sm" role="alert">{searchError}</p>}
            </div>
            <div className="space-y-2"><Label htmlFor="label-profile">Profil printer</Label>
                <Autocomplete id="label-profile" value={profileId} disabled={busy} onValueChange={setProfileId}
                    options={profiles.filter((row) => row.is_active && !row.deleted_at).map((row) => ({ value: row.id, label: `${row.name} - ${row.width_mm} x ${row.height_mm} mm` }))}
                    placeholder="Pilih printer tujuan" /></div>
            <div className="max-w-40 space-y-2"><Label htmlFor="label-copies">Jumlah label</Label><Input id="label-copies" type="number" min={1} max={100} step={1} disabled={busy} value={copies} onChange={(event) => setCopies(event.target.value)} /></div>
            {previewError && <p className="text-destructive text-sm" role="alert">{previewError}</p>}
            <Button disabled={!canPrint || !printingEnabled || busy || !selected || !profile || !preview || !Number.isInteger(Number(copies)) || Number(copies) < 1 || Number(copies) > 100}
                onClick={() => { if (selected && profile) onPrint(selected, profile, Number(copies)); }}><Printer />Kirim label</Button>
        </div>
        <div className="min-w-0 space-y-3"><h3 className="flex items-center gap-2 text-sm font-medium"><Image className="size-4 text-sky-600 dark:text-sky-300" />Pratinjau</h3>
            <div className="flex min-h-56 items-center justify-center overflow-auto border border-[var(--app-border)] bg-muted/30 p-5">
                {preview ? <img src={preview} alt="Pratinjau label barcode" className="h-auto w-full max-w-sm border bg-white" style={{ imageRendering: 'pixelated' }} /> : <p className="text-sm text-muted-foreground">Belum ada label yang dipilih.</p>}
            </div>
            {profile && <p className="break-words text-xs text-muted-foreground">{profile.printer_name} / {profile.gateway_host} / {profile.dpi} DPI</p>}
        </div>
    </section>;
}
