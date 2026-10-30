export interface CashAccount {
    id: string;
    code: string;
    name: string;
    type: string;
    is_active: boolean;
    starts_on?: string | null;
}
export interface CashRow {
    row_id: string;
    number: string;
    description: string;
    kind: string;
    direction: 'incoming' | 'outgoing';
    amount: string;
    balance: string;
    occurred_at: string;
    reversible_id: string | null;
}
export interface Statement {
    account: CashAccount | null;
    opening: { starts_on: string; amount: string } | null;
    summary: { opening: string; incoming: string; outgoing: string; closing: string; negative: boolean } | null;
    rows: CashRow[];
    meta: { current_page: number; last_page: number; total: number; per_page: number };
}
export interface CashFilters {
    account_id?: string;
    date_from?: string;
    date_to?: string;
}
export interface CashbookProps {
    accounts: CashAccount[];
    records: Statement;
    filters: CashFilters;
    today: string;
}
export type Operation = 'opening' | 'entry' | 'reverse';
export interface Attempt {
    requestId: string;
    action: Operation;
    sourceId?: string;
    data: Record<string, string | null>;
}
export interface CashDialog {
    action: Operation;
    sourceId?: string;
    reference?: string;
}
