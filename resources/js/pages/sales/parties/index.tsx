import { Autocomplete } from '@/components/shared/autocomplete';
import { MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/hooks/use-permission';
import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';
import { CustomerFormDialog } from './components/customer-form-dialog';
import { DataTable } from './components/data-table';
import { PartiesNavigation } from './components/parties-navigation';
import type { CustomerPageProps, CustomerRow } from './types';

export default function Customers({ records, filters, levelOptions }: CustomerPageProps) {
    const { can } = usePermission();
    const canManage = can('sales.parties.manage');
    const [form, setForm] = useState<{ record: CustomerRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<CustomerRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    return (
        <MasterWorkspace
            title="Pelanggan"
            module="customers"
            routePrefix="sales.customers"
            areaTitle="Penjualan"
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
            controls={
                <div className="space-y-3">
                    <PartiesNavigation />
                    <div className="flex flex-wrap items-center gap-3">
                        <Label htmlFor="level-filter">Level pelanggan</Label>
                        <Autocomplete
                            value={filters.customer_level_id ?? 'all'}
                            onValueChange={(id) =>
                                router.get(
                                    route('sales.customers.index'),
                                    { ...filters, trashed: filters.trashed ? 1 : 0, customer_level_id: id === 'all' ? null : id, page: 1 },
                                    { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter tidak valid.') },
                                )
                            }
                            id="level-filter"
                            className="w-56"
                            aria-label="Filter level"
                            options={[
                                {
                                    value: 'all',
                                    label: <>Semua level</>,
                                },
                                filters.customer_level_id &&
                                    !levelOptions.some((level) => level.id === filters.customer_level_id) && {
                                        value: filters.customer_level_id,
                                        label: <>Level tidak aktif</>,
                                    },
                                levelOptions.map((level) => ({
                                    value: level.id,
                                    label: (
                                        <>
                                            {level.code} - {level.name}
                                        </>
                                    ),
                                })),
                            ]}
                        />
                    </div>
                </div>
            }
        >
            <DataTable
                rows={records.data}
                canManage={canManage}
                onEdit={(record) => setForm({ record })}
                onLifecycle={setLifecycle}
                customerDetails={(row) => (
                    <>
                        <td className="max-w-44 px-4 py-3 break-words">{row.customer_level_name}</td>
                        <td className="max-w-56 px-4 py-3 break-words">
                            <div>{row.phone ?? '-'}</div>
                            <div className="text-muted-foreground text-xs">{row.email}</div>
                        </td>
                    </>
                )}
            />
            {canManage && form && (
                <CustomerFormDialog key={form.record?.id ?? 'new'} record={form.record} levels={levelOptions} onClose={() => setForm(null)} />
            )}
            {canManage && (
                <MasterLifecycleDialog module="customers" routePrefix="sales.customers" record={lifecycle} onClose={() => setLifecycle(null)} />
            )}
        </MasterWorkspace>
    );
}
