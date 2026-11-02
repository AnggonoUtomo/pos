import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { BarcodeLabelDialog } from './components/barcode-label-dialog';
import { CatalogNavigation } from './components/catalog-navigation';
import { ClassificationFilters } from './components/classification-filters';
import { DataTable } from './components/data-table';
import { ItemFormDialog } from './components/item-form-dialog';
import type { ItemPageProps, ItemRow } from './types';

export default function ItemIndex({ records, filters, unitOptions, priceLevels, classificationOptions }: ItemPageProps) {
    const { can } = usePermission();
    const canManage = can('inventory.manage');
    const [form, setForm] = useState<{ record: ItemRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<ItemRow | null>(null);
    const [labels, setLabels] = useState<ItemRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace
            title="Barang"
            module="items"
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
            searchPlaceholder="Cari SKU, nama, atau barcode..."
            controls={
                <div className="space-y-3">
                    <CatalogNavigation active="items" />
                    <ClassificationFilters filters={filters} options={classificationOptions} />
                </div>
            }
            extraShortcuts={[
                { label: 'Cetak label', description: 'Pada dialog label barcode', keys: ['Ctrl/Cmd', 'Enter'] },
                { label: 'Hitung harga', description: 'Pada editor harga barang', keys: ['Alt', 'G'], disabled: !canManage },
                { label: 'Terapkan usulan', description: 'Ke form, belum menyimpan', keys: ['Alt', 'A'], disabled: !canManage },
            ]}
        >
            <DataTable
                rows={records.data}
                unitOptions={unitOptions}
                canManage={canManage}
                onEdit={(record) => setForm({ record })}
                onLifecycle={setLifecycle}
                onLabels={setLabels}
            />
            {canManage && form && (
                <ItemFormDialog
                    key={form.record?.id ?? 'new'}
                    record={form.record}
                    options={unitOptions}
                    levels={priceLevels}
                    classificationOptions={classificationOptions}
                    onClose={() => setForm(null)}
                />
            )}
            {canManage && <MasterLifecycleDialog module="items" record={lifecycle} onClose={() => setLifecycle(null)} />}
            {labels && <BarcodeLabelDialog item={labels} units={unitOptions} onClose={() => setLabels(null)} />}
        </MasterWorkspace>
    );
}
