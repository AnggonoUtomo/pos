export type StockTab = 'openings' | 'balances' | 'movements';
export interface StockFilters {
    tab: StockTab;
    search: string;
    warehouse_id: string;
    item_id: string;
    status: 'all' | 'draft' | 'posted';
    trashed: boolean;
}
export interface StockLine {
    id: string;
    item_id: string;
    unit_id: string;
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
export interface StockRow {
    id: string;
    number: string;
    warehouse_id: string;
    warehouse_name: string;
    status?: 'draft' | 'posted';
    deleted_at?: string | null;
    posted_at?: string | null;
    notes?: string | null;
    sku?: string;
    item_name?: string;
    base_unit_name?: string;
    quantity?: string;
    total_value?: string;
    lines?: StockLine[];
}
export interface StockOptions {
    warehouses: { id: string; code: string; name: string }[];
    units: { id: string; code: string; name: string }[];
    items: { id: string; sku: string; name: string; base_unit_id: string; units: { unit_id: string; conversion_ratio: string }[] }[];
}
export interface StockPageProps {
    filters: StockFilters;
    records: { data: StockRow[]; meta: { current_page: number; last_page: number; per_page: number; total: number } };
    options: StockOptions;
}
export function decimal(value: string | undefined) {
    if (value === undefined) return '-';
    const [whole, fraction = ''] = value.split('.');
    const digits = fraction.replace(/0+$/, '');
    const integer = BigInt(whole);
    const sign = whole.startsWith('-') && integer === 0n && digits ? '-' : '';
    return sign + new Intl.NumberFormat('id-ID').format(integer) + (digits ? `,${digits}` : '');
}
