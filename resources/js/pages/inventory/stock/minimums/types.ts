import type { TableMeta } from '@/components/shared/table-pagination-model';

export const statusLabels = {
    all: 'Semua status',
    low: 'Perlu diperiksa',
    healthy: 'Di atas minimum',
    disabled: 'Nonaktif',
    unavailable: 'Master tidak aktif',
};
export type MinimumRow = {
    id: string;
    warehouse_id: string;
    item_id: string;
    base_unit_id: string;
    sku: string;
    item_name: string;
    warehouse_name: string;
    unit_name: string;
    minimum_quantity: string;
    quantity: string;
    shortage: string;
    is_active: boolean;
    eligible: boolean;
    status: keyof typeof statusLabels;
    version: number;
    deleted_at: string | null;
};
export type MinimumProps = {
    records: { data: MinimumRow[]; meta: TableMeta; summary: { low_count: number; empty_count: number } };
    filters: { search: string; warehouse_id: string; status: keyof typeof statusLabels; trashed: boolean };
    options: {
        warehouses: { id: string; code: string; name: string }[];
        items: { id: string; sku: string; name: string; base_unit_id: string }[];
        units: { id: string; name: string }[];
    };
};
