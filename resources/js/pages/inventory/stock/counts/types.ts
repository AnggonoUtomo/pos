import type { TableMeta } from '@/components/shared/table-pagination-model';
import type { StockOptions } from '../types';

export type CountStatus = 'counting' | 'review' | 'posted' | 'cancelled';
export const statusLabels: Record<CountStatus, string> = {
    counting: 'Penghitungan',
    review: 'Menunggu review',
    posted: 'Selesai',
    cancelled: 'Dibatalkan',
};
export interface CountRow {
    id: string;
    number: string;
    warehouse_id: string;
    warehouse_name: string;
    status: CountStatus;
    version: number;
    created_at: string;
    posted_at: string | null;
}
export interface CountLine {
    id: string;
    item_id: string;
    sku: string;
    item_name: string;
    base_unit_name: string;
    system_quantity: string;
    counted_quantity: string | null;
    difference: string | null;
    unit_cost: string | null;
}
export interface CountRecord extends CountRow {
    notes: string;
    reason: string | null;
    lines: CountLine[];
}
export interface CountIndexProps {
    records: { data: CountRow[]; meta: TableMeta };
    filters: { search: string; status: string; warehouse_id: string };
    warehouses: StockOptions['warehouses'];
    operationKey: string;
}
export interface CountShowProps {
    record: CountRecord;
    options: StockOptions;
}
