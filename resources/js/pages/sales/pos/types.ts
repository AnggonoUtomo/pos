export type WarehouseOption = { id: string; code: string; name: string };
export type CustomerOption = { id: string; code: string; name: string; levelId: string; levelName: string };
export type UnitOption = { id: string; code: string; name: string; ratio: string; barcode: string | null; price: string | null };
export type PosItem = { id: string; sku: string; name: string; base_unit_id: string; base_unit_name: string; stock: string; units: UnitOption[] };
export type CartLine = { item: PosItem; unitId: string; quantity: string };
export type CatalogResult = { items: PosItem[]; has_more: boolean; customer: CustomerOption };
export type QuoteLine = {
    item_id: string;
    unit_id: string;
    sku: string;
    name: string;
    unit_name: string;
    base_unit_id: string;
    base_unit_name: string;
    quantity: string;
    base_quantity: string;
    conversion_ratio: string;
    unit_price: string;
    subtotal: string;
};
export type CartQuote = {
    lines: QuoteLine[];
    subtotal: string;
    total: string;
    discount_amount: string;
    tax_amount: string;
    pricing: InvoicePricing;
    checked_at: string;
    customer: CustomerOption;
    warehouse: WarehouseOption;
    fingerprint: string;
};
export type PosPageProps = { warehouses: WarehouseOption[]; customers: CustomerOption[] };

export type PaymentMethod = { id: string; code: string; name: string; type: 'cash' | 'debit' | 'transfer' };
export type PaymentDraft = { methodId: string; amount: string };
export type CheckoutPayload = {
    pricing?: InvoicePricing;
    idempotency_key: string;
    warehouse_id: string;
    customer_id: string;
    quote_fingerprint: string;
    lines: { item_id: string; unit_id: string; quantity: string }[];
    payments: { method_id: string; amount: string }[];
};
export type InvoicePricing = { discount_type: 'fixed' | 'percent'; discount_value: string; tax_rate: string };
export type CheckoutAttempt = { version: 1; payload: CheckoutPayload; total: string; cart: CartLine[]; methods: PaymentMethod[] };
export type PostedInvoice = {
    company_snapshot?: { name: string; address: string; phone: string; receipt_footer: string } | null;
    id: string;
    number: string;
    warehouse_id: string;
    warehouse_code: string;
    warehouse_name: string;
    customer_id: string;
    customer_code: string;
    customer_name: string;
    customer_level_name: string;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    posted_at: string;
    lines: (QuoteLine & { id: string; line_number: number; discount_amount: string; tax_amount: string })[];
    payments: {
        total: string;
        tendered: string;
        change: string;
        payments: {
            method_id: string;
            method_name: string;
            method_type: string;
            tendered_amount: string;
            applied_amount: string;
            change_amount: string;
        }[];
    };
};
