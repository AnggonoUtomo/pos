import type { MasterRecords } from '@/components/shared/master-workspace';

export interface Choice {
    id: string;
    code: string;
    name: string;
}
export interface PurchaseLine {
    id: string;
    item_id: string;
    unit_id: string;
    base_unit_id: string;
    sku: string;
    item_name: string;
    unit_name: string;
    base_unit_name: string;
    quantity: string;
    conversion_ratio: string;
    unit_cost: string;
    base_quantity: string;
    total_value: string;
}
export interface PurchaseRow {
    id: string;
    number: string;
    status: string;
    version: number;
    supplier_id: string;
    supplier_code: string;
    supplier_name: string;
    warehouse_id: string;
    warehouse_code: string;
    warehouse_name: string;
    notes: string | null;
    deleted_at: string | null;
    created_at: string;
    total_value: string;
    lines: PurchaseLine[];
}
export interface PurchaseFilters {
    search: string;
    supplier_id: string;
    warehouse_id: string;
    trashed: boolean;
}
export interface PurchaseOptions {
    suppliers: Choice[];
    warehouses: Choice[];
    units: Choice[];
    items: { id: string; sku: string; name: string; base_unit_id: string; units: { unit_id: string; conversion_ratio: string }[] }[];
}
export interface PurchasePageProps {
    records: MasterRecords<PurchaseRow>;
    filters: PurchaseFilters;
    options: PurchaseOptions;
}
