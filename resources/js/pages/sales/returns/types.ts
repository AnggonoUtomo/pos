export type InvoiceLine = {
    id: string;
    line_number: number;
    item_id: string;
    unit_id: string;
    base_unit_id: string;
    sku: string;
    name: string;
    unit_name: string;
    base_unit_name: string;
    quantity: string;
    base_quantity: string;
    conversion_ratio: string;
    unit_price: string;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
};
export type InvoiceHeader = {
    id: string;
    number: string;
    customer_name: string;
    customer_code: string;
    customer_level_name: string;
    warehouse_name: string;
    warehouse_code: string;
    posted_at: string;
};
export type SourceInvoice = InvoiceHeader & { lines: InvoiceLine[] };
export type ReturnRow = {
    id: string;
    number: string;
    invoice_id: string;
    invoice_number: string;
    customer_name: string;
    status: 'draft';
    version: number;
    notes: string | null;
    deleted_at: string | null;
    snapshot: InvoiceHeader;
    lines: { id: string; invoice_line_id: string; quantity: string; base_quantity: string; snapshot: InvoiceLine }[];
};
export type ReturnFilters = { search: string; trashed: boolean };
export type ReturnPageProps = {
    records: { data: ReturnRow[]; meta: { current_page: number; last_page: number; total: number; per_page: number } };
    filters: ReturnFilters;
};
