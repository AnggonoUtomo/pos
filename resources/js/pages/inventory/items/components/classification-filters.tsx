import { Autocomplete } from '@/components/shared/autocomplete';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import type { ItemPageProps } from '../types';

export function ClassificationFilters({ filters, options }: { filters: ItemPageProps['filters']; options: ItemPageProps['classificationOptions'] }) {
    return (
        <div className="workspace-filters flex flex-wrap gap-3">
            {(['categories', 'brands'] as const).map((kind) => {
                const field = kind === 'categories' ? 'category_id' : 'brand_id';
                const label = kind === 'categories' ? 'Kategori' : 'Merek';
                const selected = filters[field];
                return (
                    <div key={kind} className="min-w-48 flex-1 space-y-1.5">
                        <Label htmlFor={`filter-${field}`}>{label}</Label>
                        <Autocomplete
                            id={`filter-${field}`}
                            value={selected ?? 'all'}
                            onValueChange={(id) =>
                                router.get(
                                    route('inventory.items.index'),
                                    {
                                        ...tablePageParams(),
                                        ...filters,
                                        [field]: id === 'all' ? null : id,
                                        trashed: filters.trashed ? 1 : 0,
                                        page: 1,
                                    },
                                    { preserveState: true, preserveScroll: true },
                                )
                            }
                            options={[
                                { value: 'all', label: `Semua ${label.toLowerCase()}` },
                                selected && !options[kind].some((row) => row.id === selected) && { value: selected, label: `${label} terpilih` },
                                options[kind].map((row) => ({ value: row.id, label: `${row.code} - ${row.name}` })),
                            ]}
                        />
                    </div>
                );
            })}
        </div>
    );
}
