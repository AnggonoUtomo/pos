import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DatabaseBackup, LoaderCircle } from 'lucide-react';

export function ManualBackupDialog({
    open,
    processing,
    onOpenChange,
    onConfirm,
}: {
    open: boolean;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}) {
    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                if (!processing) onOpenChange(value);
            }}
        >
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Backup database sekarang?</DialogTitle>
                    <DialogDescription>
                        Hasil disimpan pada folder backups di server toko. Backup ini tidak menggantikan salinan di perangkat terpisah.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" disabled={processing} onClick={() => onOpenChange(false)}>
                        Batal
                    </Button>
                    <Button disabled={processing} onClick={onConfirm}>
                        {processing ? <LoaderCircle className="size-4 animate-spin" /> : <DatabaseBackup className="size-4" />}
                        {processing ? 'Membuat backup...' : 'Buat backup'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
