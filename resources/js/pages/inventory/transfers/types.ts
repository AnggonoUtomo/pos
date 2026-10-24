export type WarehouseChoice = { id: string; code: string; name: string };
export type TransferLine = {
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
    base_quantity: string;
};
export type TransferRow = {
    id: string;
    number: string;
    source_warehouse_id: string;
    destination_warehouse_id: string;
    source_warehouse_code: string;
    source_warehouse_name: string;
    destination_warehouse_code: string;
    destination_warehouse_name: string;
    notes: string | null;
    status: string;
    version: number;
    deleted_at: string | null;
    created_at: string;
    lines: TransferLine[];
};
export type TransferOptions = {
    warehouses: WarehouseChoice[];
    units: { id: string; name: string }[];
    items: { id: string; sku: string; name: string; base_unit_id: string; units: { unit_id: string; conversion_ratio: string }[] }[];
};
export type TransferFilters = { search: string; source_warehouse_id: string; destination_warehouse_id: string; trashed: boolean };
export type TransferPageProps = {
    records: { data: TransferRow[]; meta: { current_page: number; last_page: number; per_page: number; total: number } };
    filters: TransferFilters;
    options: TransferOptions;
};
