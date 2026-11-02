import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ItemFormDialog } from '@/pages/inventory/items/components/item-form-dialog';
import type { ClassificationOptions, PriceLevel, UnitOption } from '@/pages/inventory/items/types';
import { SupplierFormDialog } from '@/pages/purchasing/suppliers/components/supplier-form-dialog';
import { LoaderCircle, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { Choice, PurchaseOptions } from '../types';

export type CreatedMaster = { kind: 'supplier'; choice: Choice } | { kind: 'item'; choice: PurchaseOptions['items'][number]; units: UnitOption[] };

export function MasterCreator({
    kind,
    onCreated,
    onClose,
}: {
    kind: 'supplier' | 'item';
    onCreated: (master: CreatedMaster) => void;
    onClose: () => void;
}) {
    const [metadata, setMetadata] = useState<{ units: UnitOption[]; levels: PriceLevel[]; classificationOptions: ClassificationOptions } | null>(
        null,
    );
    const [error, setError] = useState('');
    const [retry, setRetry] = useState(0);
    useEffect(() => {
        if (kind !== 'item') return;
        const controller = new AbortController();
        setError('');
        fetch(route('purchasing.purchase-orders.master-options'), {
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then(async (response) => {
                if (!response.ok) throw new Error('Pilihan satuan/level belum dapat dimuat.');
                return response.json();
            })
            .then((body) => {
                if (!Array.isArray(body.units) || !Array.isArray(body.levels)) throw new Error('Pilihan master tidak valid.');
                if (!controller.signal.aborted) setMetadata(body);
            })
            .catch((failure) => {
                if (!controller.signal.aborted) setError(failure instanceof Error ? failure.message : 'Pilihan master belum dapat dimuat.');
            });
        return () => controller.abort();
    }, [kind, retry]);
    const created = async (id: string) => {
        const response = await fetch(route('purchasing.purchase-orders.master-choice', { kind, id }), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal: AbortSignal.timeout(20000),
        });
        if (!response.ok) throw new Error('Master tersimpan tetapi pilihan belum dapat dimuat.');
        const choice = await response.json();
        if (choice.id !== id || typeof choice.name !== 'string') throw new Error('Pilihan master tidak cocok.');
        if (kind === 'item') {
            if (!metadata || !Array.isArray(choice.units) || choice.units.length === 0 || typeof choice.sku !== 'string')
                throw new Error('Barang belum dapat dipilih untuk pembelian.');
            onCreated({ kind, choice, units: metadata.units });
        } else {
            onCreated({ kind, choice });
        }
    };
    if (kind === 'supplier') return <SupplierFormDialog record={null} onCreated={created} onClose={onClose} />;
    if (metadata)
        return (
            <ItemFormDialog
                record={null}
                options={metadata.units}
                levels={metadata.levels}
                classificationOptions={metadata.classificationOptions}
                onCreated={created}
                onClose={onClose}
            />
        );
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open) onClose();
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Tambah barang</DialogTitle>
                    <DialogDescription>Persiapan data satuan dan level pelanggan</DialogDescription>
                </DialogHeader>
                {error ? (
                    <div role="alert" className="space-y-3">
                        <p className="text-destructive text-sm">{error}</p>
                        <Button type="button" variant="outline" onClick={() => setRetry((value) => value + 1)}>
                            <RefreshCw />
                            Coba lagi
                        </Button>
                    </div>
                ) : (
                    <p role="status" className="flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Memuat pilihan...
                    </p>
                )}
            </DialogContent>
        </Dialog>
    );
}
