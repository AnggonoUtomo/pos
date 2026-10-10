import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterFormDialog } from '@/components/shared/master-form-dialog';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { DataTable } from './components/data-table';
import type { UnitPageProps, UnitRow } from './types';

export default function UnitIndex({ records, filters }: UnitPageProps) {
    const { can } = usePermission();
    const canManage = can('inventory.manage');
    const [form, setForm] = useState<{ record: UnitRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<UnitRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace title="Satuan" module="units" filters={filters} meta={records.meta} canManage={canManage} onCreate={create}>
            <DataTable rows={records.data} canManage={canManage} onEdit={(record) => setForm({ record })} onLifecycle={setLifecycle} />
            {canManage && form && (
                <MasterFormDialog
                    key={form.record?.id ?? 'new'}
                    module="units"
                    title="Satuan"
                    record={form.record}
                    onClose={() => setForm(null)}
                    withSymbol
                />
            )}
            {canManage && <MasterLifecycleDialog module="units" record={lifecycle} onClose={() => setLifecycle(null)} />}
        </MasterWorkspace>
    );
}
