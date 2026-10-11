import { useForm } from '@inertiajs/react';
import { LoaderCircle, Trash2 } from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';
import { toast } from 'sonner';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import HeadingSmall from '@/components/heading-small';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({ password: '' });
    const accountError = (errors as Partial<Record<'password' | 'account', string>>).account;

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();
        if (processing) return;

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Akun berhasil dihapus.');
            },
            onError: (errors) => {
                passwordInput.current?.focus();
                toast.error('Akun belum dihapus.', { description: errors.account ?? errors.password });
            },
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setOpen(false);
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title="Hapus akun" description="Hapus akun pribadi" />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Perhatian</p>
                    <p className="text-sm">Penghapusan akun bersifat permanen.</p>
                </div>

                <Dialog
                    open={open}
                    onOpenChange={(open) => {
                        if (open) setOpen(true);
                        else if (!processing) closeModal();
                    }}
                >
                    <DialogTrigger asChild>
                        <Button variant="destructive">
                            <Trash2 className="size-4" />
                            Hapus akun
                        </Button>
                    </DialogTrigger>
                    <DialogContent
                        onEscapeKeyDown={(event) => {
                            if (processing) event.preventDefault();
                        }}
                        onPointerDownOutside={(event) => {
                            if (processing) event.preventDefault();
                        }}
                    >
                        <DialogTitle>Yakin ingin menghapus akun?</DialogTitle>
                        <DialogDescription>Masukkan password saat ini untuk mengonfirmasi penghapusan akun.</DialogDescription>
                        <form
                            className="space-y-6"
                            onSubmit={deleteUser}
                            onKeyDown={(event) => {
                                if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                                    event.preventDefault();
                                    event.currentTarget.requestSubmit();
                                }
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="delete-account-password" className="sr-only">
                                    Password
                                </Label>

                                <Input
                                    id="delete-account-password"
                                    type="password"
                                    name="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Password"
                                    autoComplete="current-password"
                                    autoFocus
                                    required
                                    disabled={processing}
                                    aria-invalid={Boolean(errors.password)}
                                />

                                <InputError message={errors.password} />
                            </div>

                            {accountError && (
                                <div role="alert">
                                    <InputError message={accountError} />
                                </div>
                            )}
                            <DialogFooter>
                                <Button type="button" variant="secondary" onClick={closeModal} disabled={processing}>
                                    Batal
                                </Button>
                                <Button type="submit" variant="destructive" disabled={processing}>
                                    {processing ? <LoaderCircle className="size-4 animate-spin" /> : <Trash2 className="size-4" />}Hapus akun
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
