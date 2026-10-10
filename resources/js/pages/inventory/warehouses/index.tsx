import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterFormDialog } from '@/components/shared/master-form-dialog';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { DataTable } from './components/data-table';
import type { WarehousePageProps, WarehouseRow } from './types';

export default function WarehouseIndex({ records, filters }: WarehousePageProps) {
    const { can } = usePermission();
    const canManage = can('inventory.manage');
    const [form, setForm] = useState<{ record: WarehouseRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<WarehouseRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace title="Gudang" module="warehouses" filters={filters} meta={records.meta} canManage={canManage} onCreate={create}>
            <DataTable rows={records.data} canManage={canManage} onEdit={(record) => setForm({ record })} onLifecycle={setLifecycle} />
            {canManage && form && (
                <MasterFormDialog
                    key={form.record?.id ?? 'new'}
                    module="warehouses"
                    title="Gudang"
                    record={form.record}
                    onClose={() => setForm(null)}
                />
            )}
            {canManage && <MasterLifecycleDialog module="warehouses" record={lifecycle} onClose={() => setLifecycle(null)} />}
        </MasterWorkspace>
    );
}
