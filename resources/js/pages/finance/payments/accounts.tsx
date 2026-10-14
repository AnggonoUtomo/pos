import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { usePermission } from '@/hooks/use-permission';
import { useCallback, useState } from 'react';
import { AccountFormDialog } from './components/account-form-dialog';
import { DataTable } from './components/data-table';
import { PaymentsNavigation } from './components/payments-navigation';
import { TypeFilter } from './components/type-filter';
import type { AccountRow, AccountsPageProps } from './types';

export default function Accounts({ records, filters }: AccountsPageProps) {
    const { can } = usePermission();
    const canManage = can('finance.payments.manage');
    const [form, setForm] = useState<{ record: AccountRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<AccountRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace
            title="Akun kas dan bank"
            module="payment-accounts"
            routePrefix="finance.payment-accounts"
            areaTitle="Finance Lite"
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
            controls={
                <div className="space-y-3">
                    <PaymentsNavigation />
                    <TypeFilter filters={filters} />
                </div>
            }
        >
            <DataTable rows={records.data} canManage={canManage} onEdit={(record) => setForm({ record })} onLifecycle={setLifecycle} />
            {canManage && form && <AccountFormDialog key={form.record?.id ?? 'new'} record={form.record} onClose={() => setForm(null)} />}
            {canManage && (
                <MasterLifecycleDialog
                    module="payment-accounts"
                    routePrefix="finance.payment-accounts"
                    record={lifecycle}
                    onClose={() => setLifecycle(null)}
                />
            )}
        </MasterWorkspace>
    );
}
