export type OperationalReport = {
    filters: { from: string; to: string; warehouse_id: string; page?: number };
    warehouses: { id: string; code: string; name: string }[];
    sales: { count: number; total: string; subtotal: string; discount_amount: string; tax_amount: string; total_cost: string };
    purchases: { count: number; total_value: string };
    sales_returns: { count: number; settled_total: string };
    purchase_returns: { count: number; settled_total: string };
    payments: { incoming: string; outgoing: string; net_movement: string; scope: string };
    stock: {
        count: number;
        total_value: string;
        truncated: boolean;
        meta: { current_page: number; last_page: number; per_page: number; total: number };
        rows: { sku: string; name: string; unit: string; warehouse: string; quantity: string; total_value: string }[];
    };
    generated_at: string;
};
