import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState } from 'react';

export type CountOperation = 'submit' | 'post' | 'cancel' | 'reopen';
const titles = { submit: 'Ajukan review opname', post: 'Posting hasil opname', cancel: 'Batalkan opname', reopen: 'Kembalikan untuk hitung ulang' };
const descriptions = {
    submit: 'Hasil hitung dikunci untuk diperiksa supervisor. Gudang tetap ditahan.',
    post: 'Selisih akan mengubah saldo dan FIFO. Hasil posted tidak dapat diedit. Gudang dibuka kembali setelah seluruh posting berhasil.',
    cancel: 'Hasil sesi tetap tersimpan tanpa koreksi stok. Gudang dibuka kembali.',
    reopen: 'Hasil hitung dapat diperbaiki kembali. Gudang tetap ditahan.',
};
export function ConfirmationDialog({
    operation,
    busy,
    error,
    onConfirm,
    onClose,
}: {
    operation: CountOperation;
    busy: boolean;
    error: string;
    onConfirm: (reason: string) => void;
    onClose: () => void;
}) {
    const [reason, setReason] = useState('');
    const submit = () => {
        if (!busy && (operation === 'submit' || reason.trim())) onConfirm(reason);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !busy) onClose();
            }}
        >
            <DialogContent
                onEscapeKeyDown={(e) => {
                    if (busy) e.preventDefault();
                }}
                onPointerDownOutside={(e) => {
                    if (busy) e.preventDefault();
                }}
                onKeyDown={(e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        submit();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>{titles[operation]}</DialogTitle>
                    <DialogDescription>{descriptions[operation]}</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                >
                    {operation !== 'submit' && (
                        <div className="space-y-2">
                            <Label htmlFor="count-reason">Alasan / hasil pemeriksaan</Label>
                            <Input
                                id="count-reason"
                                autoFocus
                                maxLength={500}
                                value={reason}
                                required
                                disabled={busy}
                                onChange={(e) => setReason(e.target.value)}
                            />
                        </div>
                    )}
                    {error && (
                        <p role="alert" className="text-destructive text-sm">
                            {error}
                        </p>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>
                            Kembali
                        </Button>
                        <Button
                            disabled={busy || (operation !== 'submit' && !reason.trim())}
                            variant={operation === 'cancel' ? 'destructive' : 'default'}
                        >
                            Konfirmasi
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
