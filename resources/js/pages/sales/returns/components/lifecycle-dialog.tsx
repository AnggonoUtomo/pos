import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import type { ReturnRow } from '../types';

export function LifecycleDialog({ record, onClose }: { record: ReturnRow; onClose: () => void }) {
    const form = useForm({ version: record.version });
    const restore = Boolean(record.deleted_at);
    const submit = () => {
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(restore ? 'Draft retur berhasil dipulihkan.' : 'Draft retur berhasil dihapus.');
                onClose();
            },
            onError: () => toast.error('Operasi belum berhasil. Muat ulang bila dokumen berubah.'),
        };
        if (restore) form.post(route('sales.returns.restore', { id: record.id }), config);
        else form.delete(route('sales.returns.destroy', { id: record.id }), config);
    };
    const errors = form.errors as Record<string, string>;
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
                    <DialogTitle>{restore ? 'Pulihkan draft retur?' : 'Hapus draft retur?'}</DialogTitle>
                    <DialogDescription className="break-all">{record.number}</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
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
                >
                    <InputError message={errors.version ?? errors.draft} />
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" variant={restore ? 'default' : 'destructive'} disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : restore ? (
                                <RotateCcw className="size-4" />
                            ) : (
                                <Trash2 className="size-4" />
                            )}
                            {restore ? 'Pulihkan' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
