import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Power } from 'lucide-react';
import { toast } from 'sonner';
import type { UserRow } from '../types';

export function UserStatusDialog({ record, onClose }: { record: UserRow; onClose: () => void }) {
    const form = useForm({ is_active: !record.is_active });
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
            >
                <DialogHeader>
                    <DialogTitle>{record.is_active ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?'}</DialogTitle>
                    <DialogDescription className="break-all">{record.name}</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (form.processing) return;
                        form.patch(route('platform.identity.users.status', { id: record.id }), {
                            preserveScroll: true,
                            onSuccess: () => {
                                toast.success('Status pengguna berhasil diperbarui.');
                                onClose();
                            },
                            onError: () => toast.error('Status pengguna belum berubah.'),
                        });
                    }}
                >
                    {Object.entries(form.errors).map(([field, message]) => (
                        <p key={field} role="alert" className="text-destructive text-sm">
                            {message}
                        </p>
                    ))}
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" variant={record.is_active ? 'destructive' : 'default'} disabled={form.processing}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Power className="size-4" />}
                            {record.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
