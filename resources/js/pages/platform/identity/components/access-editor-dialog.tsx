import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/hooks/use-permission';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { toast } from 'sonner';
import type { AccessEditor, AccessPageProps } from '../types';

const groupLabels: Record<string, string> = {
    inventory: 'Inventori',
    platform: 'Platform',
    sales: 'Penjualan',
    purchasing: 'Pembelian',
    reporting: 'Laporan',
};
export function AccessEditorDialog({
    editor,
    options,
    onClose,
}: {
    editor: Exclude<AccessEditor, { kind: 'role' }>;
    options: AccessPageProps['options'];
    onClose: () => void;
}) {
    const isUser = editor.kind === 'user';
    const { can, isSuperSystem } = usePermission();
    const [query, setQuery] = useState('');
    const form = useForm({ permissions: isUser ? [] : editor.record.permissions, roles: isUser ? editor.record.roles : [] });
    const selected = isUser ? form.data.roles : form.data.permissions;
    const lockedLastSuper = isUser && editor.record.is_active && editor.record.roles.includes('super-system') && options.active_super_users <= 1;
    const choices = (isUser ? options.roles.map((role) => role.name) : options.permissions).filter((name) =>
        name.toLowerCase().includes(query.toLowerCase()),
    );
    const eligible = (name: string) =>
        isUser
            ? name === 'super-system'
                ? isSuperSystem && !lockedLastSuper
                : options.roles.find((role) => role.name === name)?.permissions.every(can) === true
            : can(name);
    const setSelected = (names: string[]) => form.setData(isUser ? 'roles' : 'permissions', names);
    const toggle = (name: string, checked: boolean) =>
        setSelected(checked ? [...new Set([...selected, name])] : selected.filter((value) => value !== name));
    const groups = Object.groupBy(choices, (name) => (isUser ? 'roles' : name.split('.')[0]));
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        form.transform((data) => (isUser ? { roles: data.roles } : { permissions: data.permissions }));
        form.put(route(isUser ? 'platform.identity.users.roles' : 'platform.identity.roles.permissions', { id: editor.record.id }), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(isUser ? 'Role pengguna berhasil diperbarui.' : 'Permission role berhasil diperbarui.');
                onClose();
            },
            onError: () => toast.error('Akses belum tersimpan. Periksa kembali pilihan akses.'),
        });
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
                className="max-h-[90dvh] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-2xl"
                onEscapeKeyDown={(event) => {
                    if (form.processing) event.preventDefault();
                }}
                onPointerDownOutside={(event) => {
                    if (form.processing) event.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{isUser ? 'Role pengguna' : 'Permission role'}</DialogTitle>
                    <DialogDescription className="break-all">{editor.record.name}</DialogDescription>
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
                    <div className="flex items-center gap-3">
                        <Input
                            autoFocus
                            aria-label="Cari pilihan akses"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={isUser ? 'Cari role...' : 'Cari permission...'}
                            maxLength={150}
                        />
                        <span className="text-muted-foreground shrink-0 text-sm">{selected.length} dipilih</span>
                    </div>
                    {lockedLastSuper && (
                        <p className="text-sm text-amber-700 dark:text-amber-300">Pemegang super-system terakhir tidak dapat dicabut.</p>
                    )}
                    <div className="max-h-[46dvh] overflow-y-auto rounded-md border">
                        {choices.length === 0 && <p className="text-muted-foreground p-6 text-center text-sm">Tidak ada pilihan akses.</p>}
                        {Object.entries(groups).map(([group, names]) => {
                            const list = names ?? [];
                            const label = groupLabels[group] ?? (group === 'roles' ? 'Role' : group);
                            const editable = list.filter(
                                (name) => eligible(name) || (selected.includes(name) && !(name === 'super-system' && lockedLastSuper)),
                            );
                            return (
                                <fieldset key={group} className="min-w-0 border-b last:border-b-0">
                                    <legend className="sr-only">{label}</legend>
                                    <div className="bg-muted/40 flex items-center gap-2 px-4 py-3">
                                        <Checkbox
                                            aria-label={`Pilih grup ${label}`}
                                            disabled={form.processing || editable.length === 0}
                                            checked={editable.length > 0 && editable.every((name) => selected.includes(name))}
                                            onCheckedChange={(checked) =>
                                                setSelected(
                                                    checked === true
                                                        ? [...new Set([...selected, ...editable])]
                                                        : selected.filter((name) => !editable.includes(name)),
                                                )
                                            }
                                        />
                                        <span className="text-sm font-medium">{label}</span>
                                        <span className="text-muted-foreground ml-auto text-xs">{list.length}</span>
                                    </div>
                                    <div className="divide-y">
                                        {list.map((name) => (
                                            <div key={name} className="flex items-start gap-3 px-4 py-3">
                                                <Checkbox
                                                    id={`access-${name}`}
                                                    checked={selected.includes(name)}
                                                    disabled={
                                                        form.processing ||
                                                        (!eligible(name) &&
                                                            (!selected.includes(name) || (name === 'super-system' && lockedLastSuper)))
                                                    }
                                                    onCheckedChange={(checked) => toggle(name, checked === true)}
                                                />
                                                <Label htmlFor={`access-${name}`} className="min-w-0 font-mono text-xs leading-5 break-all">
                                                    {name}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </fieldset>
                            );
                        })}
                    </div>
                    {Object.entries(errors).map(([name, message]) => (
                        <p key={name} role="alert" className="text-destructive text-sm">
                            {message}
                        </p>
                    ))}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose} disabled={form.processing}>
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
