import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { DataTable } from './components/data-table';
import { MethodFormDialog } from './components/method-form-dialog';
import { PaymentsNavigation } from './components/payments-navigation';
import { TypeFilter } from './components/type-filter';
import type { MethodRow, MethodsPageProps } from './types';

export default function Methods({ records, filters, accountOptions }: MethodsPageProps) {
    const { can } = usePermission();
    const canManage = can('finance.payments.manage');
    const [form, setForm] = useState<{ record: MethodRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<MethodRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace
            title="Metode pembayaran"
            module="payment-methods"
            routePrefix="finance.payment-methods"
            areaTitle="Finance Lite"
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
            controls={
                <div className="space-y-3">
                    <PaymentsNavigation methods />
                    <TypeFilter methods filters={filters} accounts={accountOptions} />
                </div>
            }
        >
            <DataTable rows={records.data} methods canManage={canManage} onEdit={(record) => setForm({ record })} onLifecycle={setLifecycle} />
            {canManage && form && (
                <MethodFormDialog key={form.record?.id ?? 'new'} record={form.record} accounts={accountOptions} onClose={() => setForm(null)} />
            )}
            {canManage && (
                <MasterLifecycleDialog
                    module="payment-methods"
                    routePrefix="finance.payment-methods"
                    record={lifecycle}
                    onClose={() => setLifecycle(null)}
                />
            )}
        </MasterWorkspace>
    );
}
