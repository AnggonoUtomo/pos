import { MasterActions, MasterLifecycleDialog } from '@/components/shared/master-actions';
import { MasterWorkspace } from '@/components/shared/master-workspace';
import { Badge } from '@/components/ui/badge';
import { usePermission } from '@/hooks/use-permission';
import { Shapes, Tag } from 'lucide-react';
import { useCallback, useState } from 'react';
import { CatalogNavigation } from '../catalog-navigation';
import { ClassificationFormDialog } from './form-dialog';
import type { ClassificationKind, ClassificationPageProps, ClassificationRow } from './types';

export function ClassificationWorkspace({ kind, records, filters }: ClassificationPageProps & { kind: ClassificationKind }) {
    const { can } = usePermission();
    const canManage = can('inventory.manage');
    const [form, setForm] = useState<{ record: ClassificationRow | null } | null>(null);
    const [lifecycle, setLifecycle] = useState<ClassificationRow | null>(null);
    const create = useCallback(() => setForm({ record: null }), []);
    const title = kind === 'categories' ? 'Kategori barang' : 'Merek barang';
    const Icon = kind === 'categories' ? Shapes : Tag;
    return (
        <MasterWorkspace
            title={title}
            module={kind}
            filters={filters}
            meta={records.meta}
            canManage={canManage}
            onCreate={create}
            controls={<CatalogNavigation active={kind} />}
        >
            <table className="workspace-table w-full text-sm">
                <thead>
                    <tr>
                        {['Kode', 'Nama', 'Status', ...(canManage ? ['Operasi'] : [])].map((label) => (
                            <th
                                key={label}
                                scope="col"
                                className={label === 'Operasi' ? 'px-4 py-3 text-right font-medium' : 'px-4 py-3 text-left font-medium'}
                            >
                                {label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {records.data.length ? (
                        records.data.map((row) => (
                            <tr key={row.id}>
                                <td className="px-4 py-3 font-mono text-xs whitespace-nowrap">{row.code}</td>
                                <td className="max-w-sm px-4 py-3 font-medium break-words">
                                    <span className="flex items-center gap-2">
                                        <Icon className="size-4 shrink-0 text-sky-600 dark:text-sky-300" />
                                        {row.name}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant="outline"
                                        className={
                                            row.is_active
                                                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                        }
                                    >
                                        {row.is_active ? 'Aktif' : 'Nonaktif'}
                                    </Badge>
                                </td>
                                {canManage && (
                                    <td className="px-3 py-1">
                                        <MasterActions record={row} onEdit={() => setForm({ record: row })} onLifecycle={() => setLifecycle(row)} />
                                    </td>
                                )}
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan={3 + Number(canManage)} className="text-muted-foreground px-4 py-16 text-center">
                                <Icon className="mx-auto mb-3 size-7 text-amber-600 dark:text-amber-300" />
                                Tidak ada data {title.toLowerCase()}.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
            {canManage && form && <ClassificationFormDialog kind={kind} record={form.record} onClose={() => setForm(null)} />}
            {canManage && <MasterLifecycleDialog module={kind} record={lifecycle} onClose={() => setLifecycle(null)} />}
        </MasterWorkspace>
    );
}
