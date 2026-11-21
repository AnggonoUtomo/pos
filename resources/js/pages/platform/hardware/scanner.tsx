import { BarcodeInput } from '@/components/shared/barcode-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Activity, CircleCheck, Clock3, ScanBarcode, ShieldCheck, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { ScanResults } from './components/scan-results';
import { useScannerDiagnostics } from './hooks/use-scanner-diagnostics';

export default function ScannerWorkspace() {
    const permitted = usePermission().can('inventory.view');
    const { results, pending, busy, enqueue, clear } = useScannerDiagnostics();
    const [confirmClear, setConfirmClear] = useState(false);
    const found = results.filter((row) => row.status === 'found').length;
    const hasResults = results.length > 0 || busy;

    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if (!event.altKey || event.ctrlKey || event.metaKey || document.querySelector('[role="dialog"]')) return;
            if (event.key.toLowerCase() === 's') {
                event.preventDefault();
                document.getElementById('hardware-scan-code')?.focus();
            }
            if (event.key.toLowerCase() === 'r' && hasResults) {
                event.preventDefault();
                setConfirmClear(true);
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [hasResults]);

    return (
        <AppLayout breadcrumbs={[{ title: 'Uji scanner', href: route('platform.hardware.scanner.index') }]}>
            <Head title="Uji scanner" />
            <WorkspaceShortcutDropdown
                title="Uji scanner"
                description="Perangkat POS"
                shortcuts={[
                    { label: 'Fokus scanner', description: 'Fokus input kode', keys: ['Alt', 'S'] },
                    { label: 'Bersihkan hasil', description: 'Konfirmasi bersihkan hasil uji', keys: ['Alt', 'R'], disabled: !hasResults },
                ]}
            />
            <div className="min-w-0 flex-1 space-y-6 p-4 lg:p-6">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="flex items-center gap-2 text-xl font-semibold">
                            <ScanBarcode className="size-5 text-cyan-600 dark:text-cyan-300" />
                            Uji barcode satuan jual
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">Pemeriksaan identitas barang, tanpa transaksi</p>
                    </div>
                    <Badge variant="outline" className="gap-1.5 text-amber-700 dark:text-amber-300">
                        <Activity className="size-3.5" />
                        Perangkat fisik belum diuji
                    </Badge>
                </header>
                <div className="grid min-w-0 gap-6 border-y border-[var(--app-border)] bg-[var(--app-panel)] px-4 py-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                    <BarcodeInput id="hardware-scan-code" disabled={!permitted} busy={busy} onScan={enqueue} />
                    <dl className="grid grid-cols-2 gap-4">
                        <div>
                            <dt className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                <Clock3 className="size-4 text-amber-600 dark:text-amber-300" />
                                Menunggu hasil
                            </dt>
                            <dd className="mt-2 text-xl font-semibold" data-testid="scan-pending">
                                {pending}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                <CircleCheck className="size-4 text-emerald-600 dark:text-emerald-300" />
                                Dikenali dalam hasil terakhir
                            </dt>
                            <dd className="mt-2 text-xl font-semibold">{found}</dd>
                        </div>
                    </dl>
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-muted-foreground flex items-center gap-2 text-sm">
                        <ShieldCheck className="size-4 shrink-0 text-emerald-600 dark:text-emerald-300" />
                        Stok, harga, keranjang dan pembayaran tidak berubah.
                    </p>
                    <Button variant="outline" disabled={!hasResults} onClick={() => setConfirmClear(true)}>
                        <Trash2 className="text-rose-600 dark:text-rose-300" />
                        Bersihkan hasil
                    </Button>
                </div>
                <div className="sr-only" role="status" aria-live="polite">
                    {busy ? `${pending} uji menunggu hasil` : results[0] ? `Uji ${results[0].id}: ${results[0].message}` : 'Belum ada hasil uji'}
                </div>
                <ScanResults results={results} />
            </div>
            <Dialog open={confirmClear} onOpenChange={setConfirmClear}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Bersihkan hasil uji?</DialogTitle>
                        <DialogDescription>
                            Hasil sementara dan antrean uji akan dibersihkan. Data barang dan transaksi tidak dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmClear(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={() => {
                                clear();
                                setConfirmClear(false);
                                toast.success('Hasil uji dibersihkan.');
                            }}
                        >
                            <Trash2 />
                            Bersihkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
