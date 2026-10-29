import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Check, LoaderCircle, Mail, Save, ShieldCheck, UserRound } from 'lucide-react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil akun',
        href: '/settings/profile',
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user?.name ?? '',
        email: auth.user?.email ?? '',
    });

    if (!auth.user) return null;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (processing) return;

        patch(route('profile.update'), {
            onSuccess: () => toast.success('Profil diperbarui'),
            onError: () =>
                toast.error('Gagal menyimpan profil', {
                    description: 'Periksa kembali nama dan email yang diisi.',
                }),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profil akun" />

            <SettingsLayout>
                <div className="min-w-0 space-y-6">
                    <header className="flex flex-wrap items-center gap-4 border-y border-sky-500/20 bg-sky-500/5 px-5 py-5">
                        <Avatar className="size-14 shrink-0 rounded-lg">
                            <AvatarImage src={auth.user.avatar} alt="" />
                            <AvatarFallback className="rounded-lg bg-sky-500/15 text-lg font-semibold text-sky-700 dark:text-sky-300">
                                {getInitials(auth.user.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1 space-y-1">
                            <h1 className="text-lg font-semibold [overflow-wrap:anywhere]">{auth.user.name}</h1>
                            <p className="text-muted-foreground text-sm [overflow-wrap:anywhere]">{auth.user.email}</p>
                            <Badge
                                variant="outline"
                                className="mt-1 gap-1.5 border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                            >
                                <ShieldCheck className="size-3.5" aria-hidden="true" /> Akun pribadi
                            </Badge>
                        </div>
                    </header>
                    <form onSubmit={submit} className="border-y border-[var(--app-border)] bg-[var(--app-panel)]">
                        <div className="flex items-center gap-3 border-b border-[var(--app-border)] px-5 py-4">
                            <UserRound className="size-5 shrink-0 text-violet-600 dark:text-violet-300" aria-hidden="true" />
                            <h2 className="text-base font-semibold">Informasi profil</h2>
                        </div>
                        <div className="space-y-5 p-5">
                            <div className="grid gap-2">
                                <Label htmlFor="name" className="flex items-center gap-2">
                                    <UserRound className="size-4 text-violet-600 dark:text-violet-300" aria-hidden="true" /> Nama
                                </Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    autoComplete="name"
                                    placeholder="Nama lengkap"
                                    aria-invalid={Boolean(errors.name)}
                                    aria-describedby={errors.name ? 'name-error' : undefined}
                                />

                                <InputError id="name-error" message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email" className="flex items-center gap-2">
                                    <Mail className="size-4 text-sky-600 dark:text-sky-300" aria-hidden="true" /> Alamat email
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoComplete="username"
                                    placeholder="nama@contoh.com"
                                    aria-invalid={Boolean(errors.email)}
                                    aria-describedby={errors.email ? 'email-error' : undefined}
                                />

                                <InputError id="email-error" message={errors.email} />
                            </div>

                            {mustVerifyEmail && auth.user.email_verified_at === null && (
                                <div className="space-y-2 border-l-2 border-amber-500 bg-amber-500/5 p-3 text-sm">
                                    <p>
                                        Email Anda belum diverifikasi.{' '}
                                        <Link
                                            href={route('verification.send')}
                                            method="post"
                                            as="button"
                                            className="text-primary focus-visible:outline-ring rounded-sm underline underline-offset-4 focus-visible:outline-2"
                                        >
                                            Kirim ulang email verifikasi
                                        </Link>
                                    </p>

                                    {status === 'verification-link-sent' && (
                                        <div role="status" className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                            Tautan verifikasi baru telah dikirim.
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                        <div className="flex flex-wrap items-center gap-3 border-t border-[var(--app-border)] bg-[var(--app-panel-strong)] px-5 py-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <LoaderCircle className="size-4 animate-spin motion-reduce:animate-none" />
                                ) : (
                                    <Save className="size-4" />
                                )}
                                Simpan perubahan
                            </Button>
                            {recentlySuccessful && (
                                <p role="status" className="flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-300">
                                    <Check className="size-4" /> Perubahan tersimpan.
                                </p>
                            )}
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
