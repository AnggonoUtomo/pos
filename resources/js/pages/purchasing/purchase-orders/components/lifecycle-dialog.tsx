import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import type { PurchaseRow } from '../types';

export function LifecycleDialog({ record, onClose }: { record: PurchaseRow; onClose: () => void }) {
    const form = useForm({ version: record.version });
    const deleted = Boolean(record.deleted_at);
    const errors = form.errors as Record<string, string>;
    const submit = () => {
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(deleted ? 'Draft berhasil dipulihkan.' : 'Draft berhasil dihapus.');
                onClose();
            },
            onError: () => toast.error('Operasi belum berhasil. Periksa pesan kesalahan.'),
        };
        if (deleted) form.post(route('purchasing.purchase-orders.restore', { id: record.id }), config);
        else form.delete(route('purchasing.purchase-orders.destroy', { id: record.id }), config);
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
            >
                <DialogHeader>
                    <DialogTitle>{deleted ? 'Pulihkan draft?' : 'Hapus draft?'}</DialogTitle>
                    <DialogDescription className="break-all">{record.number}</DialogDescription>
                </DialogHeader>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                    onKeyDown={(e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                            e.preventDefault();
                            e.currentTarget.requestSubmit();
                        }
                    }}
                    className="space-y-4"
                >
                    <InputError message={errors.purchase_order ?? errors.version} />
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" variant={deleted ? 'default' : 'destructive'} disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : deleted ? (
                                <RotateCcw className="size-4" />
                            ) : (
                                <Trash2 className="size-4" />
                            )}
                            {deleted ? 'Pulihkan' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
