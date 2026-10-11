import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import type { RoleRow } from '../types';

export function RoleFormDialog({ record, deleting = false, onClose }: { record: RoleRow | null; deleting?: boolean; onClose: () => void }) {
    const form = useForm({ name: record?.name ?? '' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(deleting ? 'Role berhasil dihapus.' : 'Role berhasil disimpan.');
                onClose();
            },
            onError: () => toast.error('Role belum tersimpan. Periksa kembali data akses.'),
        };
        if (deleting && record) form.delete(route('platform.identity.roles.destroy', { id: record.id }), options);
        else if (record) form.put(route('platform.identity.roles.update', { id: record.id }), options);
        else form.post(route('platform.identity.roles.store'), options);
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
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{deleting ? 'Hapus role?' : record ? 'Edit role' : 'Tambah role'}</DialogTitle>
                    <DialogDescription className={deleting ? 'break-all' : 'sr-only'}>
                        {deleting ? record?.name : 'Data role akses'}
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={submit}
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}
                >
                    {!deleting && (
                        <div className="space-y-2">
                            <Label htmlFor="role-name">Nama role</Label>
                            <Input
                                autoFocus
                                id="role-name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="kasir"
                                required
                                maxLength={64}
                                disabled={form.processing}
                                aria-invalid={Boolean(errors.name)}
                            />
                            <InputError message={errors.name} />
                        </div>
                    )}
                    {Object.entries(errors)
                        .filter(([name]) => name !== 'name')
                        .map(([name, message]) => (
                            <p key={name} role="alert" className="text-destructive text-sm">
                                {message}
                            </p>
                        ))}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" variant={deleting ? 'destructive' : 'default'} disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : deleting ? (
                                <Trash2 className="size-4" />
                            ) : (
                                <Save className="size-4" />
                            )}
                            {deleting ? 'Hapus' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
