export interface ReturnSourceLine {
    id: string;
    sku: string;
    item_name: string;
    unit_name: string;
    base_unit_name: string;
    quantity: string;
    base_quantity: string;
    conversion_ratio: string;
    unit_cost: string;
}

export interface ReturnPurchaseSource {
    id: string;
    number: string;
    supplier_name: string;
    warehouse_name: string;
    posted_at: string;
    lines: ReturnSourceLine[];
}

export interface ReturnLine {
    id: string;
    purchase_order_line_id: string;
    quantity: string;
    base_quantity: string;
    snapshot: ReturnSourceLine;
}

export interface ReturnRow {
    id: string;
    number: string;
    purchase_order_id: string;
    purchase_order_number: string;
    supplier_name: string;
    status: 'draft' | 'posted';
    version: number;
    stock_issue_id: string | null;
    posted_at: string | null;
    notes: string | null;
    snapshot: Omit<ReturnPurchaseSource, 'lines'>;
    lines: ReturnLine[];
    deleted_at: string | null;
    created_at: string;
}

export interface ReturnFilters {
    search: string;
    status: 'all' | 'draft' | 'posted';
    trashed: boolean;
}

export interface ReturnPageProps {
    records: {
        data: ReturnRow[];
        meta: { current_page: number; last_page: number; per_page: number; total: number };
    };
    filters: ReturnFilters;
}
