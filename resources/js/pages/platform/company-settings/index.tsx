import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Save, Store } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

type Settings = { name: string; address: string; phone: string; receipt_footer: string; version: number };

export default function CompanySettings({ settings }: { settings: Settings }) {
    const { can } = usePermission();
    const editable = can('platform.company-settings.manage');
    const form = useForm(settings);
    const formRef = useRef<HTMLFormElement>(null);
    const { setData } = form;
    useEffect(() => setData(settings), [settings, setData]);
    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && editable) {
                event.preventDefault();
                formRef.current?.requestSubmit();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [editable]);
    return (
        <AppLayout breadcrumbs={[{ title: 'Pengaturan toko', href: route('platform.company-settings.index') }]}>
            <Head title="Pengaturan toko" />
            <WorkspaceShortcutDropdown
                title="Pengaturan toko"
                shortcuts={[{ label: 'Simpan', description: 'Simpan pengaturan', keys: ['Ctrl/Cmd', 'Enter'], disabled: !editable }]}
            />
            <section className="min-w-0 flex-1 space-y-6 p-4 lg:p-6">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <Store className="size-5 text-cyan-600 dark:text-cyan-300" />
                    Pengaturan toko
                </h1>
                <form
                    ref={formRef}
                    className="max-w-2xl space-y-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (!editable || form.processing) return;
                        form.put(route('platform.company-settings.update'), {
                            preserveScroll: true,
                            onSuccess: () => toast.success('Pengaturan toko tersimpan.'),
                            onError: () => toast.error('Pengaturan belum tersimpan. Periksa isian atau muat ulang.'),
                        });
                    }}
                >
                    <fieldset disabled={!editable || form.processing} className="space-y-5">
                        {(
                            [
                                { key: 'name', label: 'Nama toko', max: 150 },
                                { key: 'address', label: 'Alamat', max: 500 },
                                { key: 'phone', label: 'Telepon', max: 50 },
                                { key: 'receipt_footer', label: 'Catatan struk', max: 300 },
                            ] as const
                        ).map(({ key, label, max }) => (
                            <div key={key} className="space-y-2">
                                <Label htmlFor={`company-${key}`}>{label}</Label>
                                <Input
                                    id={`company-${key}`}
                                    value={form.data[key]}
                                    maxLength={max}
                                    onChange={(e) => form.setData(key, e.target.value)}
                                />
                                <InputError message={form.errors[key]} />
                            </div>
                        ))}
                    </fieldset>
                    <InputError message={form.errors.version ?? (form.errors as Record<string, string>).settings} />
                    {editable && (
                        <Button disabled={form.processing}>
                            {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}Simpan pengaturan
                        </Button>
                    )}
                </form>
            </section>
        </AppLayout>
    );
}
