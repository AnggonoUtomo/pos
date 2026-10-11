import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/hooks/use-permission';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import type { AccessPageProps, UserRow } from '../types';

export function UserFormDialog({ record, options, onClose }: { record: UserRow | null; options: AccessPageProps['options']; onClose: () => void }) {
    const { can, isSuperSystem } = usePermission();
    const form = useForm({ name: record?.name ?? '', email: record?.email ?? '', password: '', password_confirmation: '', roles: [] as string[] });
    const errors = form.errors as Record<string, string>;
    const fields = record ? (['name', 'email'] as const) : (['name', 'email', 'password', 'password_confirmation'] as const);
    const labels = { name: 'Nama pengguna', email: 'Email pengguna', password: 'Password awal', password_confirmation: 'Konfirmasi password' };
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        form.transform((data) => (record ? { name: data.name, email: data.email } : data));
        const callbacks = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Pengguna berhasil disimpan.');
                onClose();
            },
            onError: () => toast.error('Pengguna belum tersimpan. Periksa kembali data.'),
            onFinish: () => form.reset('password', 'password_confirmation'),
        };
        if (record) form.put(route('platform.identity.users.update', { id: record.id }), callbacks);
        else form.post(route('platform.identity.users.store'), callbacks);
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !form.processing) onClose();
            }}
        >
            <DialogContent
                className="max-h-[90dvh] overflow-y-auto sm:max-w-xl"
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{record ? 'Edit pengguna' : 'Tambah pengguna'}</DialogTitle>
                    <DialogDescription className={record ? 'break-all' : 'sr-only'}>{record?.name ?? 'Data akun pengguna'}</DialogDescription>
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
                    {fields.map((field) => {
                        const password = field.startsWith('password');
                        return (
                            <div key={field} className="space-y-2">
                                <Label htmlFor={`user-${field}`}>{labels[field]}</Label>
                                <Input
                                    id={`user-${field}`}
                                    type={password ? 'password' : field === 'email' ? 'email' : 'text'}
                                    autoFocus={field === 'name'}
                                    required
                                    disabled={form.processing}
                                    autoComplete={password ? 'new-password' : 'off'}
                                    minLength={password ? 12 : undefined}
                                    maxLength={password ? 72 : 255}
                                    aria-invalid={Boolean(errors[field])}
                                    value={form.data[field]}
                                    onChange={(event) => form.setData(field, event.target.value)}
                                />
                                <InputError message={errors[field]} />
                            </div>
                        );
                    })}
                    {!record && can('platform.identity.manage') && (
                        <fieldset className="space-y-2">
                            <legend className="mb-2 text-sm font-medium">Role pengguna</legend>
                            <div className="max-h-40 space-y-3 overflow-y-auto rounded-md border p-3">
                                {options.roles.length === 0 && <p className="text-muted-foreground text-sm">Belum ada role.</p>}
                                {options.roles.map((role) => {
                                    const eligible =
                                        role.name !== 'super-admin' && (role.name === 'super-system' ? isSuperSystem : role.permissions.every(can));
                                    return (
                                        <div key={role.id} className="flex items-start gap-3">
                                            <Checkbox
                                                id={`new-user-role-${role.id}`}
                                                checked={form.data.roles.includes(role.name)}
                                                disabled={form.processing || !eligible}
                                                onCheckedChange={(checked) =>
                                                    form.setData(
                                                        'roles',
                                                        checked === true
                                                            ? [...form.data.roles, role.name]
                                                            : form.data.roles.filter((name) => name !== role.name),
                                                    )
                                                }
                                            />
                                            <Label htmlFor={`new-user-role-${role.id}`} className="min-w-0 text-sm break-all">
                                                {role.name}
                                            </Label>
                                        </div>
                                    );
                                })}
                            </div>
                        </fieldset>
                    )}
                    {Object.entries(errors)
                        .filter(([field]) => !fields.some((name) => name === field))
                        .map(([field, message]) => (
                            <p key={field} role="alert" className="text-destructive text-sm">
                                {message}
                            </p>
                        ))}
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
