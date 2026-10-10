import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { DataTable } from './components/data-table';
import { ItemFormDialog } from './components/item-form-dialog';
import type { ItemPageProps, ItemRow } from './types';

export default function ItemIndex({ records, filters, unitOptions }: ItemPageProps) {
    const { can } = usePermission();
    const canManage = can('inventory.manage');
    const [form, setForm] = useState<{ record: ItemRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<ItemRow | null>(null);
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
        >
            <DataTable
                rows={records.data}
                unitOptions={unitOptions}
                canManage={canManage}
                onEdit={(record) => setForm({ record })}
                onLifecycle={setLifecycle}
            />
            {canManage && form && (
                <ItemFormDialog key={form.record?.id ?? 'new'} record={form.record} options={unitOptions} onClose={() => setForm(null)} />
            )}
            {canManage && <MasterLifecycleDialog module="items" record={lifecycle} onClose={() => setLifecycle(null)} />}
        </MasterWorkspace>
    );
}
