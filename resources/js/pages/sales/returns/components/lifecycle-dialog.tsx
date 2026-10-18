import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { CheckCheck, LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import type { ReturnRow } from '../types';

export function LifecycleDialog({ record, onClose, mode = 'lifecycle' }: { record: ReturnRow; onClose: () => void; mode?: 'lifecycle' | 'post' }) {
    const form = useForm({ version: record.version });
    const restore = Boolean(record.deleted_at);
    const posting = mode === 'post';
    const submit = () => {
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    posting ? 'Barang retur berhasil diterima.' : restore ? 'Draft retur berhasil dipulihkan.' : 'Draft retur berhasil dihapus.',
                );
                onClose();
            },
            onError: () => toast.error('Operasi belum berhasil. Muat ulang bila dokumen berubah.'),
        };
        if (posting) form.post(route('sales.returns.post', { id: record.id }), config);
        else if (restore) form.post(route('sales.returns.restore', { id: record.id }), config);
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
                    <DialogTitle>{posting ? 'Terima barang retur?' : restore ? 'Pulihkan draft retur?' : 'Hapus draft retur?'}</DialogTitle>
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
                    {posting && (
                        <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">Invoice asal</dt>
                            <dd className="font-mono text-xs break-all">{record.invoice_number}</dd>
                            <dt className="text-muted-foreground">Pelanggan</dt>
                            <dd className="break-words">{record.customer_name}</dd>
                            <dt className="text-muted-foreground">Gudang penerimaan</dt>
                            <dd className="break-words">{record.snapshot.warehouse_name}</dd>
                            <dt className="text-muted-foreground">Baris barang</dt>
                            <dd>{record.lines.length}</dd>
                        </dl>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" variant={posting || restore ? 'default' : 'destructive'} disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : posting ? (
                                <CheckCheck className="size-4" />
                            ) : restore ? (
                                <RotateCcw className="size-4" />
                            ) : (
                                <Trash2 className="size-4" />
                            )}
                            {posting ? 'Terima barang' : restore ? 'Pulihkan' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
