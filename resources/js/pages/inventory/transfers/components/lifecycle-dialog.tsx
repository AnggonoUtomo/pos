import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import type { TransferRow } from '../types';

export function LifecycleDialog({ record, onClose }: { record: TransferRow; onClose: () => void }) {
    const form = useForm({ version: record.version });
    const busy = useRef(false);
    const deleted = Boolean(record.deleted_at);
    const submit = () => {
        if (form.processing || busy.current) return;
        busy.current = true;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(deleted ? 'Draft berhasil dipulihkan.' : 'Draft berhasil dihapus.');
                onClose();
            },
            onError: () => toast.error('Operasi belum berhasil. Periksa revisi draft.'),
            onFinish: () => {
                busy.current = false;
            },
        };
        if (deleted) form.post(route('inventory.transfers.restore', { id: record.id }), config);
        else form.delete(route('inventory.transfers.destroy', { id: record.id }), config);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                onEscapeKeyDown={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (form.processing) e.preventDefault();
                }}
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        submit();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>{deleted ? 'Pulihkan draft transfer?' : 'Hapus draft transfer?'}</DialogTitle>
                    <DialogDescription className="break-all">
                        {record.number} / Revisi {record.version}
                    </DialogDescription>
                </DialogHeader>
                <InputError message={(form.errors as Record<string, string>).version ?? (form.errors as Record<string, string>).transfer} />
                <DialogFooter>
                    <Button variant="outline" disabled={form.processing} onClick={onClose}>
                        Batal
                    </Button>
                    <Button variant={deleted ? 'default' : 'destructive'} disabled={form.processing} onClick={submit}>
                        {form.processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : deleted ? (
                            <RotateCcw className="size-4" />
                        ) : (
                            <Trash2 className="size-4" />
                        )}
                        {deleted ? 'Pulihkan draft' : 'Hapus draft'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
