import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { DataTable } from './components/data-table';
import { SupplierFormDialog } from './components/supplier-form-dialog';
import type { SupplierPageProps, SupplierRow } from './types';

export default function Suppliers({ records, filters }: SupplierPageProps) {
    const { can } = usePermission();
    const canManage = can('purchasing.suppliers.manage');
    const [form, setForm] = useState<{ record: SupplierRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<SupplierRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace
            title="Supplier"
            module="suppliers"
            routePrefix="purchasing.suppliers"
            areaTitle="Pembelian"
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
        >
            <DataTable rows={records.data} canManage={canManage} onEdit={(record) => setForm({ record })} onLifecycle={setLifecycle} />
            {canManage && form && <SupplierFormDialog key={form.record?.id ?? 'new'} record={form.record} onClose={() => setForm(null)} />}
            {canManage && (
                <MasterLifecycleDialog module="suppliers" routePrefix="purchasing.suppliers" record={lifecycle} onClose={() => setLifecycle(null)} />
            )}
        </MasterWorkspace>
    );
}
