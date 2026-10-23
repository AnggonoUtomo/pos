import type { MasterRecords } from '@/components/shared/master-workspace';

export type Direction = 'incoming' | 'outgoing';
export interface PaymentRow {
    id: string;
    direction: Direction;
    source_type: 'sale' | 'purchase' | 'sale_return' | 'purchase_return';
    source_number: string;
    occurred_at: string;
    total: string;
}
export interface PaymentDetail extends PaymentRow {
    tendered: string | null;
    change: string | null;
    payments: {
        method_id: string;
        method_name: string;
        method_code: string;
        method_type: string;
        account_id: string;
        account_name: string;
        account_code: string;
        account_type: string;
        amount: string;
        tendered_amount?: string;
        change_amount?: string;
    }[];
}
export interface HistoryFilters {
    search: string;
    direction: Direction | 'all';
    account_id: string | null;
    method_id: string | null;
    date_from: string | null;
    date_to: string | null;
}
export interface HistoryOptions {
    accounts: { id: string; name: string }[];
    methods: { id: string; name: string }[];
}
export interface HistoryProps {
    records: MasterRecords<PaymentRow>;
    filters: HistoryFilters;
    options: HistoryOptions;
}
export const directionLabel = (direction: Direction) => (direction === 'incoming' ? 'Kas masuk' : 'Kas keluar');
export const sourceLabel = (source: PaymentRow['source_type']) =>
    ({ sale: 'Penjualan', purchase: 'Pembelian', sale_return: 'Refund retur', purchase_return: 'Uang retur supplier' })[source];
export const dateLabel = (value: string) =>
    new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value));
