export interface HistoryFilters {
    search: string;
    date_from: string | null;
    date_to: string | null;
    warehouse_id: string | null;
    customer_id: string | null;
}

export interface InvoiceRow {
    id: string;
    number: string;
    posted_at: string;
    customer_name: string;
    customer_level_name: string;
    warehouse_name: string;
    total: string;
}

export interface HistoryOption {
    id: string;
    name: string;
}

export interface HistoryPageProps {
    records: {
        data: InvoiceRow[];
        meta: { current_page: number; last_page: number; per_page: number; total: number };
    };
    filters: HistoryFilters;
    viewAll: boolean;
    warehouseOptions: HistoryOption[];
    customerOptions: HistoryOption[];
}
