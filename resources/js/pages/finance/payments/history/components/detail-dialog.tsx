import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatDecimalInput as format } from '@/lib/decimal-input';
import { LoaderCircle, RefreshCw } from 'lucide-react';
import type { usePaymentDetail } from '../hooks/use-payment-detail';
import { dateLabel, directionLabel } from '../types';

export function DetailDialog({ state }: { state: ReturnType<typeof usePaymentDetail> }) {
    const { selected, detail, loading, error, close, retry } = state;
    return (
        <Dialog
            open={Boolean(selected)}
            onOpenChange={(open) => {
                if (!open) close();
            }}
        >
            <DialogContent className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Detail pembayaran</DialogTitle>
                    <DialogDescription className="break-all">{selected?.source_number}</DialogDescription>
                </DialogHeader>
                {loading && (
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin text-sky-600" />
                        Memuat detail...
                    </p>
                )}
                {error && (
                    <div role="alert" className="flex items-center justify-between gap-3 text-sm">
                        <span className="text-destructive">{error}</span>
                        <Button size="icon" variant="outline" title="Muat ulang detail" aria-label="Muat ulang detail" onClick={retry}>
                            <RefreshCw className="size-4 text-sky-600" />
                        </Button>
                    </div>
                )}
                {detail && (
                    <div className="min-w-0 space-y-4">
                        <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">Arah</dt>
                            <dd>{directionLabel(detail.direction)}</dd>
                            <dt className="text-muted-foreground">Waktu (WIB)</dt>
                            <dd>{dateLabel(detail.occurred_at)}</dd>
                            <dt className="text-muted-foreground">Total dokumen</dt>
                            <dd className="font-semibold break-words">Rp {format(detail.total)}</dd>
                            {detail.tendered !== null && (
                                <>
                                    <dt className="text-muted-foreground">Diterima</dt>
                                    <dd className="break-words">Rp {format(detail.tendered)}</dd>
                                    <dt className="text-muted-foreground">Kembalian</dt>
                                    <dd className="break-words">Rp {format(detail.change ?? '0')}</dd>
                                </>
                            )}
                        </dl>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[520px] text-sm">
                                <thead className="bg-muted/40 border-b">
                                    <tr>
                                        {['Metode', 'Akun', 'Jumlah bersih'].map((label) => (
                                            <th key={label} scope="col" className="px-3 py-3 text-left font-medium">
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {detail.payments.map((line) => (
                                        <tr key={line.method_id}>
                                            <td className="px-3 py-3">
                                                <div>{line.method_name}</div>
                                                <span className="text-muted-foreground text-xs">{line.method_code}</span>
                                            </td>
                                            <td className="px-3 py-3">
                                                <div>{line.account_name}</div>
                                                <span className="text-muted-foreground text-xs">{line.account_code}</span>
                                            </td>
                                            <td className="px-3 py-3 text-right whitespace-nowrap">Rp {format(line.amount)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
                <DialogFooter>
                    <Button variant="outline" onClick={close}>
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
