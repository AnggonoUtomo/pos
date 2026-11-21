import { Autocomplete } from '@/components/shared/autocomplete';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { hardwareRequest } from '../hooks/use-label-print';
import type { GatewayPrinter, PrinterProfile } from '../types';

export function PrinterProfileForm({ profile, host, printers, hosts, onClose, onSaved }: {
    profile: PrinterProfile | null; host: string; printers: GatewayPrinter[]; hosts: string[]; onClose: () => void; onSaved: () => void;
}) {
    const [form, setForm] = useState({ name: profile?.name ?? '', printer_name: profile?.printer_name ?? '', gateway_host: profile?.gateway_host ?? host,
        width_mm: String(profile?.width_mm ?? 50), height_mm: String(profile?.height_mm ?? 30), dpi: 203,
        orientation: profile?.orientation ?? 'portrait', is_active: profile?.is_active ?? true, version: profile?.version ?? 0 });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const names = [...new Set([...printers.map((row) => row.name), ...(profile ? [profile.printer_name] : [])])];
    const save = async () => {
        if (busy) return;
        setBusy(true); setError('');
        try {
            await hardwareRequest(`/platform/hardware/printers${profile ? `/${profile.id}` : ''}`, profile ? 'PATCH' : 'POST', {
                ...form, width_mm: Number(form.width_mm), height_mm: Number(form.height_mm),
            });
            toast.success('Profil printer disimpan.'); onSaved(); onClose();
        } catch (failure) { setError(failure instanceof Error ? failure.message : 'Profil belum dapat disimpan.'); toast.error('Profil belum disimpan.'); }
        finally { setBusy(false); }
    };
    return <Dialog open onOpenChange={(open) => { if (!open && !busy) onClose(); }}><DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
        <DialogHeader><DialogTitle>{profile ? 'Edit profil printer' : 'Tambah profil printer'}</DialogTitle></DialogHeader>
        <form onSubmit={(event) => { event.preventDefault(); void save(); }} className="space-y-4">
            <div className="space-y-2"><Label htmlFor="printer-name">Nama profil</Label><Input id="printer-name" required maxLength={150} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} /></div>
            <div className="space-y-2"><Label htmlFor="printer-os">Printer OS</Label><Autocomplete id="printer-os" value={form.printer_name} onValueChange={(value) => setForm({ ...form, printer_name: value })} options={names.map((name) => ({ value: name, label: name }))} placeholder="Pilih printer hasil deteksi" /></div>
            <div className="space-y-2"><Label htmlFor="printer-host">Gateway</Label><Autocomplete id="printer-host" value={form.gateway_host} onValueChange={(value) => setForm({ ...form, gateway_host: value })} options={hosts.map((value) => ({ value, label: value }))} /></div>
            <div className="grid grid-cols-2 gap-4">{(['width_mm', 'height_mm'] as const).map((field) => <div className="space-y-2" key={field}>
                <Label htmlFor={`printer-${field}`}>{field === 'width_mm' ? 'Lebar (mm)' : 'Tinggi (mm)'}</Label>
                <Input id={`printer-${field}`} type="number" min={20} max={field === 'width_mm' ? 100 : 150} step={1} required value={form[field]} onChange={(event) => setForm({ ...form, [field]: event.target.value })} />
            </div>)}</div>
            <div className="grid grid-cols-2 gap-4"><div className="space-y-2"><Label htmlFor="printer-orientation">Orientasi</Label><Select value={form.orientation} onValueChange={(orientation: 'portrait' | 'landscape') => setForm({ ...form, orientation })}><SelectTrigger id="printer-orientation"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="portrait">Potret</SelectItem><SelectItem value="landscape">Lanskap</SelectItem></SelectContent></Select></div>
                <div className="space-y-2"><Label htmlFor="printer-dpi">Resolusi</Label><Input id="printer-dpi" value="203 DPI" readOnly /></div></div>
            <div className="flex items-center gap-2"><Checkbox id="printer-active" checked={form.is_active} onCheckedChange={(value) => setForm({ ...form, is_active: value === true })} /><Label htmlFor="printer-active">Profil aktif</Label></div>
            {error && <p role="alert" className="text-destructive text-sm">{error}</p>}
            <DialogFooter><Button type="button" variant="outline" onClick={onClose} disabled={busy}>Batal</Button><Button type="submit" disabled={busy || !form.printer_name}><Save />Simpan</Button></DialogFooter>
        </form>
    </DialogContent></Dialog>;
}
