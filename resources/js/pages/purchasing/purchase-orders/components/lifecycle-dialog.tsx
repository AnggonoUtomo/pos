import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatDecimalInput } from '@/lib/decimal-input';
import { useForm } from '@inertiajs/react';
import { CheckCheck, LoaderCircle, RotateCcw, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import type { PurchaseRow } from '../types';

export function LifecycleDialog({ record, onClose, mode = 'lifecycle' }: { record: PurchaseRow; onClose: () => void; mode?: 'lifecycle' | 'post' }) {
    const form = useForm({ version: record.version });
    const deleted = Boolean(record.deleted_at);
    const posting = mode === 'post';
    const errors = form.errors as Record<string, string>;
    const submit = () => {
        if (form.processing) return;
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    posting ? 'Seluruh barang pembelian berhasil diterima.' : deleted ? 'Draft berhasil dipulihkan.' : 'Draft berhasil dihapus.',
                );
                onClose();
            },
            onError: () => toast.error('Operasi belum berhasil. Periksa pesan kesalahan.'),
        };
        if (posting) form.post(route('purchasing.purchase-orders.post', { id: record.id }), config);
        else if (deleted) form.post(route('purchasing.purchase-orders.restore', { id: record.id }), config);
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
                    <DialogTitle>{posting ? 'Terima seluruh barang?' : deleted ? 'Pulihkan draft?' : 'Hapus draft?'}</DialogTitle>
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
                    {posting && (
                        <dl className="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">Supplier</dt>
                            <dd className="break-words">{record.supplier_name}</dd>
                            <dt className="text-muted-foreground">Gudang</dt>
                            <dd className="break-words">{record.warehouse_name}</dd>
                            <dt className="text-muted-foreground">Baris barang</dt>
                            <dd>{record.lines.length}</dd>
                            <dt className="text-muted-foreground">Total pembelian</dt>
                            <dd className="font-semibold break-words">Rp {formatDecimalInput(record.total_value)}</dd>
                        </dl>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" variant={posting || deleted ? 'default' : 'destructive'} disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : posting ? (
                                <CheckCheck className="size-4 text-emerald-600 dark:text-emerald-300" />
                            ) : deleted ? (
                                <RotateCcw className="size-4" />
                            ) : (
                                <Trash2 className="size-4" />
                            )}
                            {posting ? 'Terima barang' : deleted ? 'Pulihkan' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
