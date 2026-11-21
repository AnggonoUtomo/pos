import { BarcodeInput } from '@/components/shared/barcode-input';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { CircleCheck, ScanBarcode, Trash2 } from 'lucide-react';
import { useState } from 'react';

export function ScannerTestPanel({ expected, allowLookup }: { expected?: string; allowLookup: boolean }) {
    const [codes, setCodes] = useState<string[]>([]);
    return <section className="space-y-5">
        <h2 className="flex items-center gap-2 text-base font-semibold"><ScanBarcode className="size-5 text-violet-600 dark:text-violet-300" />Tes input scanner</h2>
        <BarcodeInput id="hardware-keyboard-test" disabled={false} onScan={(code) => setCodes((rows) => [code, ...rows].slice(0, 20))} />
        <div className="flex flex-wrap items-center justify-between gap-3"><p className="text-sm text-muted-foreground">{codes.length} hasil sementara; identitas perangkat belum diverifikasi.</p><Button variant="outline" disabled={!codes.length} onClick={() => setCodes([])}><Trash2 className="text-rose-600" />Bersihkan</Button></div>
        {expected && <p className="break-all font-mono text-sm">Kode pembanding: {expected}</p>}
        <ol className="divide-y divide-[var(--app-border)] border-y border-[var(--app-border)]">{codes.map((code, index) => <li className="flex flex-wrap items-center justify-between gap-2 py-3" key={`${index}-${code}`}><code className="break-all text-sm">{code}</code>{expected && <span className={code === expected ? 'text-emerald-600 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'}>{code === expected ? <span className="flex items-center gap-1 text-sm"><CircleCheck className="size-4" />Sesuai</span> : 'Berbeda'}</span>}</li>)}</ol>
        {allowLookup && <Button variant="outline" asChild><Link href="/platform/hardware/scanner"><ScanBarcode className="text-cyan-600" />Diagnostik satuan jual</Link></Button>}
    </section>;
}
