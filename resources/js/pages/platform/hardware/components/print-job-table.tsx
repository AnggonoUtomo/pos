import { TablePagination } from '@/components/shared/table-pagination';
import type { TableMeta } from '@/components/shared/table-pagination-model';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Printer, RotateCcw, X } from 'lucide-react';
import { useState } from 'react';
import type { LabelJob } from '../types';

export const jobStatusLabels = { queued: 'Siap dikirim', sending: 'Menunggu konfirmasi', submitted: 'Diterima gateway', failed_before_send: 'Tidak dikirim', uncertain: 'Hasil belum pasti' };

export function PrintJobTable({ history, actorId, canPrint, canReprint, manage, disabled, printingEnabled, onSend, onCancel, onReprint }: {
    history: { data: LabelJob[]; meta: TableMeta }; actorId: number; canPrint: boolean; canReprint: boolean; manage: boolean; disabled: boolean; printingEnabled: boolean;
    onSend: (job: LabelJob) => void; onCancel: (job: LabelJob) => void; onReprint: (job: LabelJob, reason: string, key: string) => Promise<void>;
}) {
    const [selected, setSelected] = useState<LabelJob | null>(null);
    const [reason, setReason] = useState('');
    const [key, setKey] = useState('');
    return <div className="space-y-4">
        <div className="overflow-auto"><Table><TableHeader><TableRow><TableHead>Job / waktu WIB</TableHead><TableHead>Label</TableHead><TableHead>Printer</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Operasi</TableHead></TableRow></TableHeader><TableBody>
            {history.data.map((job) => {
                const expired = job.status === 'sending' && job.expires_at !== null && Date.parse(job.expires_at) < Date.now();
                const allowed = canPrint && (job.created_by === actorId || manage);
                return <TableRow key={job.id}><TableCell><span className="font-mono text-xs">{job.id}</span><div className="mt-1 text-xs text-muted-foreground">{new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(job.created_at))}</div><div className="text-xs text-muted-foreground">Operator #{job.created_by}</div></TableCell>
                    <TableCell className="min-w-40"><span className="font-medium">{job.snapshot.name}</span><div className="text-xs text-muted-foreground">{job.snapshot.unit_name} / {job.copies} label</div><code className="break-all text-xs">{job.snapshot.barcode}</code>{job.original_job_id && <div className="mt-1 max-w-64 break-words text-xs text-muted-foreground">Cetak ulang: {job.reason}</div>}</TableCell>
                    <TableCell><div>{job.printer.name}</div><div className="text-xs text-muted-foreground">{job.printer.printer_name}</div></TableCell>
                    <TableCell><Badge variant="outline" className={job.status === 'submitted' ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'}>{expired ? 'Hasil belum pasti' : jobStatusLabels[job.status]}</Badge></TableCell>
                    <TableCell><div className="flex justify-end gap-1">
                        {job.status === 'queued' && job.created_by === actorId && canPrint && <><Button variant="ghost" size="icon" title="Kirim job" aria-label="Kirim job" disabled={disabled || !printingEnabled} onClick={() => onSend(job)}><Printer className="text-sky-600" /></Button><Button variant="ghost" size="icon" title="Batalkan sebelum dikirim" aria-label="Batalkan sebelum dikirim" disabled={disabled} onClick={() => onCancel(job)}><X className="text-rose-600" /></Button></>}
                        {allowed && canReprint && (job.status === 'submitted' || job.status === 'failed_before_send' || job.status === 'uncertain' || expired) && <Button variant="ghost" size="icon" title="Cetak ulang beralasan" aria-label="Cetak ulang beralasan" disabled={disabled || !printingEnabled} onClick={() => { setSelected(job); setReason(''); setKey(crypto.randomUUID()); }}><RotateCcw className="text-violet-600" /></Button>}
                    </div></TableCell></TableRow>;
            })}
            {!history.data.length && <TableRow><TableCell colSpan={5} className="h-28 text-center text-muted-foreground">Belum ada job label.</TableCell></TableRow>}
        </TableBody></Table></div>
        <TablePagination meta={history.meta} disabled={disabled} />
        <Dialog open={selected !== null} onOpenChange={(open) => { if (!open && !disabled) setSelected(null); }}><DialogContent><DialogHeader><DialogTitle>Cetak ulang label</DialogTitle></DialogHeader>
            <div className="space-y-2"><Label htmlFor="label-reprint-reason">Alasan setelah memeriksa hasil fisik</Label><Input id="label-reprint-reason" value={reason} onChange={(event) => setReason(event.target.value)} maxLength={500} /></div>
            <DialogFooter><Button variant="outline" disabled={disabled} onClick={() => setSelected(null)}>Batal</Button><Button disabled={disabled || reason.trim().length < 5} onClick={async () => { if (selected) { await onReprint(selected, reason.trim(), key); setSelected(null); } }}><RotateCcw />Buat cetak ulang</Button></DialogFooter>
        </DialogContent></Dialog>
    </div>;
}
