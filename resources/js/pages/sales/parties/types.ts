import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export interface LevelRow extends MasterRecord {
    code: string;
}
export interface CustomerRow extends LevelRow {
    customer_level_id: string;
    customer_level_name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
}
export interface LevelOption {
    id: string;
    code: string;
    name: string;
}
export interface LevelPageProps {
    records: MasterRecords<LevelRow>;
    filters: MasterFilters;
}
export interface CustomerPageProps {
    records: MasterRecords<CustomerRow>;
    filters: MasterFilters & { customer_level_id: string | null };
    levelOptions: LevelOption[];
}
