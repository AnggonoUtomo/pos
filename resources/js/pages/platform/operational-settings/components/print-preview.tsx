import { ReceiptPaperSurface, ReceiptPrintPortal } from '@/components/shared/receipt-print';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { ReceiptPaper } from '@/types';
import { Printer } from 'lucide-react';

function SampleReceipt() {
    return (
        <article className="space-y-3 p-1 text-xs" data-testid="sample-receipt">
            <header className="space-y-1 text-center">
                <h2 className="font-semibold">UJI CETAK</h2>
                <p>Bukan bukti transaksi</p>
            </header>
            <p className="border-y py-2">CONTOH-0001</p>
            <table className="w-full table-fixed text-xs">
                <tbody>
                    <tr className="border-b align-top">
                        <td className="py-2 break-words">Kamera CCTV Outdoor PoE Resolusi Tinggi</td>
                        <td className="py-2 text-right">2 pcs</td>
                        <td className="py-2 text-right break-words">Rp200.000</td>
                        <td className="py-2 text-right break-words">Rp400.000</td>
                    </tr>
                </tbody>
            </table>
            <dl className="grid grid-cols-2 gap-2">
                <dt>Total</dt>
                <dd className="text-right">Rp400.000</dd>
                <dt>Tunai</dt>
                <dd className="text-right">Rp450.000</dd>
                <dt>Kembalian</dt>
                <dd className="text-right">Rp50.000</dd>
            </dl>
            <p className="border-t pt-2 text-center">Terima kasih</p>
        </article>
    );
}

export function PrintPreview({ open, onOpenChange, paper }: { open: boolean; onOpenChange: (value: boolean) => void; paper: ReceiptPaper }) {
    return (
        <>
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent
                    className="max-h-[90dvh] overflow-y-auto sm:max-w-lg"
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'p') {
                            event.preventDefault();
                            window.print();
                        }
                    }}
                >
                    <DialogHeader>
                        <DialogTitle>Uji cetak</DialogTitle>
                        <DialogDescription>{paper === 'a4' ? 'A4' : paper === '58mm' ? '58 mm' : '80 mm'}</DialogDescription>
                    </DialogHeader>
                    <div className="overflow-x-auto border bg-white p-3">
                        <ReceiptPaperSurface paper={paper}>
                            <SampleReceipt />
                        </ReceiptPaperSurface>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => onOpenChange(false)}>
                            Tutup
                        </Button>
                        <Button onClick={() => window.print()}>
                            <Printer className="text-emerald-300" />
                            Cetak contoh
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            {open && (
                <ReceiptPrintPortal paper={paper}>
                    <SampleReceipt />
                </ReceiptPrintPortal>
            )}
        </>
    );
}
