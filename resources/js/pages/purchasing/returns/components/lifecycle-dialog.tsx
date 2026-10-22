import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, RotateCcw, Trash2, Truck } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import type { ReturnRow } from '../types';

export function ReturnLifecycleDialog({ record, onClose, mode = 'lifecycle' }: { record: ReturnRow; onClose: () => void; mode?: 'lifecycle' | 'post' }) {
    const deleted = Boolean(record.deleted_at);
    const posting = mode === 'post';
    const form = useForm({ version: record.version });
    const submitting = useRef(false);
    const submit = () => {
        if (form.processing || submitting.current) return;
        submitting.current = true;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(posting ? 'Barang retur berhasil diserahkan.' : deleted ? 'Draft retur berhasil dipulihkan.' : 'Draft retur berhasil dihapus.');
                onClose();
            },
            onError: () => toast.error(posting ? 'Barang belum diserahkan. Periksa sisa stok dan revisi draft.' : 'Operasi belum berhasil. Periksa revisi draft.'),
            onFinish: () => { submitting.current = false; },
        };
        if (posting) form.post(route('purchasing.returns.post', { id: record.id }), config);
        else if (deleted) form.post(route('purchasing.returns.restore', { id: record.id }), config);
        else form.delete(route('purchasing.returns.destroy', { id: record.id }), config);
    };
    return <Dialog open onOpenChange={(open) => !open && !form.processing && onClose()}>
        <DialogContent onEscapeKeyDown={(event) => form.processing && event.preventDefault()} onPointerDownOutside={(event) => form.processing && event.preventDefault()} onKeyDown={(event) => { if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { event.preventDefault(); submit(); } }}>
            <DialogHeader><DialogTitle>{posting ? 'Serahkan barang retur?' : deleted ? 'Pulihkan draft retur?' : 'Hapus draft retur?'}</DialogTitle><DialogDescription>{record.number} · Revisi {record.version}</DialogDescription></DialogHeader>
            {posting && <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm"><dt className="text-muted-foreground">Pembelian asal</dt><dd className="break-all">{record.purchase_order_number}</dd><dt className="text-muted-foreground">Supplier</dt><dd className="break-words">{record.supplier_name}</dd><dt className="text-muted-foreground">Gudang asal</dt><dd className="break-words">{record.snapshot.warehouse_name}</dd><dt className="text-muted-foreground">Barang</dt><dd>{record.lines.length} baris</dd></dl>}
            <p className="text-muted-foreground text-sm">{posting ? 'Posting dilakukan saat barang diserahkan. Seluruh baris akan mengurangi stok dari penerimaan asal dan dokumen menjadi readonly. Pengembalian uang supplier dicatat terpisah.' : deleted ? 'Draft akan kembali ke daftar aktif tanpa mengubah pembelian asal.' : 'Draft akan dihapus secara lunak. Tidak ada perubahan stok atau pembayaran.'}</p>
            <InputError message={(form.errors as Record<string, string>).version ?? (form.errors as Record<string, string>).purchase_return ?? (form.errors as Record<string, string>).draft} />
            <DialogFooter>
                <Button variant="outline" disabled={form.processing} onClick={onClose}>Batal</Button>
                <Button variant={posting || deleted ? 'default' : 'destructive'} disabled={form.processing} onClick={submit}>
                    {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : posting ? <Truck className="size-4" /> : deleted ? <RotateCcw className="size-4" /> : <Trash2 className="size-4" />}
                    {posting ? 'Serahkan barang' : deleted ? 'Pulihkan draft' : 'Hapus draft'}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>;
}
