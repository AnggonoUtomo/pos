import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export interface UnitOption {
    id: string;
    code: string;
    name: string;
    symbol: string;
}
export interface ItemUnitInput {
    unit_id: string;
    conversion_ratio: string;
    barcode: string | null;
    is_sale_unit: boolean;
    is_purchase_unit: boolean;
}
export interface ItemRow extends MasterRecord {
    sku: string;
    base_unit_id: string;
    units: (ItemUnitInput & { id: string; item_id: string })[];
}
export interface ItemPageProps {
    records: MasterRecords<ItemRow>;
    filters: MasterFilters;
    unitOptions: UnitOption[];
}

export function baseRow(unitId: string): ItemUnitInput {
    return { unit_id: unitId, conversion_ratio: '1', barcode: null, is_sale_unit: true, is_purchase_unit: true };
}
