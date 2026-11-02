import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { toast } from 'sonner';
import type { ClassificationKind, ClassificationRow } from './types';

export function ClassificationFormDialog({
    kind,
    record,
    onClose,
}: {
    kind: ClassificationKind;
    record: ClassificationRow | null;
    onClose: () => void;
}) {
    const form = useForm({ code: record?.code ?? '', name: record?.name ?? '', is_active: record?.is_active ?? true });
    const label = kind === 'categories' ? 'kategori' : 'merek';
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
                        {record ? 'Edit' : 'Tambah'} {label}
                    </DialogTitle>
                    <DialogDescription className="sr-only">Form {label} barang</DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (form.processing) return;
                        const config = {
                            preserveScroll: true,
                            onSuccess: () => {
                                toast.success('Master berhasil disimpan.');
                                onClose();
                            },
                            onError: () => toast.error('Periksa isian master.'),
                        };
                        if (record) form.put(route(`inventory.${kind}.update`, { id: record.id }), config);
                        else form.post(route(`inventory.${kind}.store`), config);
                    }}
                    onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}
                >
                    <fieldset disabled={form.processing} className="space-y-4">
                        {(['code', 'name'] as const).map((field) => (
                            <div key={field} className="space-y-2">
                                <Label htmlFor={`classification-${field}`}>{field === 'code' ? 'Kode' : 'Nama'}</Label>
                                <Input
                                    id={`classification-${field}`}
                                    value={form.data[field]}
                                    onChange={(event) => form.setData(field, event.target.value)}
                                    autoFocus={field === 'code'}
                                    required
                                    maxLength={field === 'code' ? 32 : 150}
                                    aria-invalid={Boolean(errors[field])}
                                />
                                <InputError message={errors[field]} />
                            </div>
                        ))}
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="classification-active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                            />
                            <Label htmlFor="classification-active">Aktif</Label>
                        </div>
                        <InputError message={errors.is_active ?? errors.inventory} />
                    </fieldset>
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
