import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { router } from '@inertiajs/react';
import { LoaderCircle, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import type { MasterRecord } from './master-workspace';

export function MasterActions({ record, onEdit, onLifecycle }: { record: MasterRecord; onEdit: () => void; onLifecycle: () => void }) {
    const deleted = Boolean(record.deleted_at);
    return (
        <div className="flex justify-end gap-1">
            {!deleted && (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button variant="ghost" size="icon" aria-label={`Edit ${record.name}`} onClick={onEdit}>
                            <Pencil className="size-4 text-sky-600 dark:text-sky-300" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Edit</TooltipContent>
                </Tooltip>
            )}
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button variant="ghost" size="icon" aria-label={`${deleted ? 'Pulihkan' : 'Hapus'} ${record.name}`} onClick={onLifecycle}>
                        {deleted ? (
                            <RotateCcw className="size-4 text-emerald-600 dark:text-emerald-300" />
                        ) : (
                            <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                        )}
                    </Button>
                </TooltipTrigger>
                <TooltipContent>{deleted ? 'Pulihkan' : 'Hapus'}</TooltipContent>
            </Tooltip>
        </div>
    );
}

export function MasterLifecycleDialog({
    module,
    record,
    onClose,
    routePrefix = `inventory.${module}`,
}: {
    module: string;
    record: MasterRecord | null;
    onClose: () => void;
    routePrefix?: string;
}) {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');
    const deleted = Boolean(record?.deleted_at);
    const close = () => {
        if (!processing) {
            setError('');
            onClose();
        }
    };
    const submit = () => {
        if (!record || processing) return;
        setProcessing(true);
        setError('');
        router.visit(route(`${routePrefix}.${deleted ? 'restore' : 'destroy'}`, { id: record.id }), {
            method: deleted ? 'post' : 'delete',
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(deleted ? 'Data berhasil dipulihkan.' : 'Data berhasil dihapus.');
                onClose();
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] || 'Operasi gagal.';
                setError(message);
                toast.error(message);
            },
            onFinish: () => setProcessing(false),
        });
    };
    return (
        <Dialog
            open={Boolean(record)}
            onOpenChange={(open) => {
                if (!open) close();
            }}
        >
            <DialogContent
                onEscapeKeyDown={(event) => {
                    if (processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{deleted ? 'Pulihkan data?' : 'Hapus data?'}</DialogTitle>
                    <DialogDescription className="break-words">{record?.name}</DialogDescription>
                </DialogHeader>
                {error && (
                    <p className="text-destructive text-sm" role="alert">
                        {error}
                    </p>
                )}
                <DialogFooter>
                    <Button variant="outline" disabled={processing} onClick={close}>
                        Batal
                    </Button>
                    <Button variant={deleted ? 'default' : 'destructive'} disabled={processing} onClick={submit}>
                        {processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : deleted ? (
                            <RotateCcw className="size-4" />
                        ) : (
                            <Trash2 className="size-4" />
                        )}
                        {deleted ? 'Pulihkan' : 'Hapus'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
