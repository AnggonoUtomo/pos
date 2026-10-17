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
    stock_receipt_id: string | null;
    posted_at: string | null;
    posted_from_version: number | null;
    payment_disbursement_id: string | null;
    paid_at: string | null;
    paid_from_version: number | null;
    payment_snapshot: { total: string; payments: { method_id: string; method_name: string; method_type: string; amount: string }[] } | null;
}
export interface PurchaseFilters {
    search: string;
    supplier_id: string;
    warehouse_id: string;
    trashed: boolean;
    status: 'all' | 'draft' | 'posted';
    payment_status: 'all' | 'paid' | 'unrecorded';
}
export interface PurchaseOptions {
    suppliers: Choice[];
    warehouses: Choice[];
    units: Choice[];
    items: { id: string; sku: string; name: string; base_unit_id: string; units: { unit_id: string; conversion_ratio: string }[] }[];
    paymentMethods: (Choice & { type: string })[];
}
export interface PurchasePageProps {
    records: MasterRecords<PurchaseRow>;
    filters: PurchaseFilters;
    options: PurchaseOptions;
}
