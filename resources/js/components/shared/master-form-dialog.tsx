import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { type FormEvent } from 'react';
import { toast } from 'sonner';
import type { MasterRecord } from './master-workspace';

export function MasterFormDialog({
    module,
    title,
    record,
    onClose,
    withSymbol = false,
    routePrefix = `inventory.${module}`,
    errorKey = 'inventory',
}: {
    module: string;
    title: string;
    record: (MasterRecord & { code: string; symbol?: string }) | null;
    onClose: () => void;
    withSymbol?: boolean;
    routePrefix?: string;
    errorKey?: string;
}) {
    const form = useForm({ code: record?.code ?? '', name: record?.name ?? '', symbol: record?.symbol ?? '', is_active: record?.is_active ?? true });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
                onClose();
            },
            onError: () => toast.error('Data belum tersimpan. Periksa isian form.'),
        };
        if (record) form.put(route(`${routePrefix}.update`, { id: record.id }), options);
        else form.post(route(`${routePrefix}.store`), options);
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
                    <DialogTitle>
                        {record ? 'Edit' : 'Tambah'} {title.toLowerCase()}
                    </DialogTitle>
                    <DialogDescription className="sr-only">Form {title.toLowerCase()}</DialogDescription>
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
                    <div className="space-y-2">
                        <Label htmlFor="master-code">Kode</Label>
                        <Input
                            autoFocus
                            id="master-code"
                            value={form.data.code}
                            onChange={(event) => form.setData('code', event.target.value)}
                            maxLength={32}
                            required
                            aria-invalid={Boolean(errors.code)}
                        />
                        <InputError message={errors.code} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="master-name">Nama</Label>
                        <Input
                            id="master-name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            maxLength={150}
                            required
                            aria-invalid={Boolean(errors.name)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    {withSymbol && (
                        <div className="space-y-2">
                            <Label htmlFor="master-symbol">Simbol</Label>
                            <Input
                                id="master-symbol"
                                value={form.data.symbol}
                                onChange={(event) => form.setData('symbol', event.target.value)}
                                maxLength={16}
                                required
                                aria-invalid={Boolean(errors.symbol)}
                            />
                            <InputError message={errors.symbol} />
                        </div>
                    )}
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="master-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                        />
                        <Label htmlFor="master-active">Aktif</Label>
                    </div>
                    <InputError message={errors[errorKey] ?? errors.is_active} />
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
