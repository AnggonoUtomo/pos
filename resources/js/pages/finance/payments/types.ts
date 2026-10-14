import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export type AccountType = 'cash' | 'bank';
export type MethodType = 'cash' | 'debit' | 'transfer';
export type AccountRow = MasterRecord & {
    code: string;
    type: AccountType;
    bank_name: string | null;
    account_number: string | null;
    account_holder: string | null;
};
export type MethodRow = MasterRecord & {
    code: string;
    type: MethodType;
    payment_account_id: string;
    account_name: string;
    account_type: AccountType;
};
export type AccountOption = Pick<AccountRow, 'id' | 'code' | 'name' | 'type'>;
export type PaymentFilters = MasterFilters & { type: string | null; payment_account_id: string | null };
export type AccountsPageProps = { records: MasterRecords<AccountRow>; filters: PaymentFilters };
export type MethodsPageProps = { records: MasterRecords<MethodRow>; filters: PaymentFilters; accountOptions: AccountOption[] };
export const accountTypes = { cash: 'Kas', bank: 'Bank' };
export const methodTypes = { cash: 'Tunai', debit: 'Debit', transfer: 'Transfer' };
