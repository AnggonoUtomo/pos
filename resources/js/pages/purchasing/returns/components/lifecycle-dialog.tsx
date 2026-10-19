import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import type { ReturnRow } from '../types';

export function ReturnLifecycleDialog({ record, onClose }: { record: ReturnRow; onClose: () => void }) {
    const deleted = Boolean(record.deleted_at);
    const form = useForm({ version: record.version });
    const submitting = useRef(false);
    const submit = () => {
        if (form.processing || submitting.current) return;
        submitting.current = true;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(deleted ? 'Draft retur berhasil dipulihkan.' : 'Draft retur berhasil dihapus.');
                onClose();
            },
            onError: () => toast.error('Operasi belum berhasil. Periksa revisi draft.'),
            onFinish: () => { submitting.current = false; },
        };
        if (deleted) form.post(route('purchasing.returns.restore', { id: record.id }), config);
        else form.delete(route('purchasing.returns.destroy', { id: record.id }), config);
    };
    return <Dialog open onOpenChange={(open) => !open && !form.processing && onClose()}>
        <DialogContent onEscapeKeyDown={(event) => form.processing && event.preventDefault()} onPointerDownOutside={(event) => form.processing && event.preventDefault()}>
            <DialogHeader><DialogTitle>{deleted ? 'Pulihkan draft retur?' : 'Hapus draft retur?'}</DialogTitle><DialogDescription>{record.number} · Revisi {record.version}</DialogDescription></DialogHeader>
            <p className="text-muted-foreground text-sm">{deleted ? 'Draft akan kembali ke daftar aktif tanpa mengubah pembelian asal.' : 'Draft akan dihapus secara lunak. Tidak ada perubahan stok atau pembayaran.'}</p>
            <InputError message={(form.errors as Record<string, string>).version ?? (form.errors as Record<string, string>).purchase_return} />
            <DialogFooter>
                <Button variant="outline" disabled={form.processing} onClick={onClose}>Batal</Button>
                <Button variant={deleted ? 'default' : 'destructive'} disabled={form.processing} onClick={submit}>
                    {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : deleted ? <RotateCcw className="size-4" /> : <Trash2 className="size-4" />}
                    {deleted ? 'Pulihkan draft' : 'Hapus draft'}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>;
}
