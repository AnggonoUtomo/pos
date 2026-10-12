import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterFormDialog } from '@/components/shared/master-form-dialog';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { DataTable } from './components/data-table';
import { PartiesNavigation } from './components/parties-navigation';
import type { LevelPageProps, LevelRow } from './types';

export default function CustomerLevels({ records, filters }: LevelPageProps) {
    const { can } = usePermission();
    const canManage = can('sales.parties.manage');
    const [form, setForm] = useState<{ record: LevelRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<LevelRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace
            title="Level pelanggan"
            module="customer-levels"
            routePrefix="sales.customer-levels"
            areaTitle="Penjualan"
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
            controls={<PartiesNavigation levels />}
        >
            <DataTable rows={records.data} canManage={canManage} onEdit={(record) => setForm({ record })} onLifecycle={setLifecycle} />
            {canManage && form && (
                <MasterFormDialog
                    key={form.record?.id ?? 'new'}
                    title="Level pelanggan"
                    module="customer-levels"
                    routePrefix="sales.customer-levels"
                    errorKey="parties"
                    record={form.record}
                    onClose={() => setForm(null)}
                />
            )}
            {canManage && (
                <MasterLifecycleDialog
                    module="customer-levels"
                    routePrefix="sales.customer-levels"
                    record={lifecycle}
                    onClose={() => setLifecycle(null)}
                />
            )}
        </MasterWorkspace>
    );
}
