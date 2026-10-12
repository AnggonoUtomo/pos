import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { CheckCheck, LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import type { StockRow } from '../types';

export function OpeningActionDialog({ record, action, onClose }: { record: StockRow; action: 'post' | 'destroy' | 'restore'; onClose: () => void }) {
    const form = useForm({});
    const label = action === 'post' ? 'Posting' : action === 'restore' ? 'Pulihkan' : 'Hapus';
    const submit = () => {
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Stok awal berhasil ${action === 'post' ? 'diposting' : action === 'restore' ? 'dipulihkan' : 'dihapus'}.`);
                onClose();
            },
            onError: () => toast.error('Operasi stok awal belum berhasil.'),
        };
        if (action === 'destroy') form.delete(route('inventory.stock.openings.destroy', { id: record.id }), config);
        else form.post(route(`inventory.stock.openings.${action}`, { id: record.id }), config);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onKeyDown={(event) => {
                    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                        event.preventDefault();
                        submit();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>{label} stok awal?</DialogTitle>
                    <DialogDescription className="break-all">{record.number}</DialogDescription>
                </DialogHeader>
                {action === 'post' && (
                    <p className="text-muted-foreground text-sm">
                        Saldo stok akan bertambah. Dokumen yang sudah diposting tidak dapat diedit atau dihapus.
                    </p>
                )}
                <InputError message={(form.errors as Record<string, string>).stock} />
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={form.processing}>
                        Batal
                    </Button>
                    <Button variant={action === 'destroy' ? 'destructive' : 'default'} onClick={submit} disabled={form.processing}>
                        {form.processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : action === 'post' ? (
                            <CheckCheck className="size-4" />
                        ) : action === 'restore' ? (
                            <RotateCcw className="size-4" />
                        ) : (
                            <Trash2 className="size-4" />
                        )}
                        {label}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
