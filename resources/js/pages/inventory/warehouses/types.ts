import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export interface WarehouseRow extends MasterRecord {
    code: string;
}
export interface WarehousePageProps {
    records: MasterRecords<WarehouseRow>;
    filters: MasterFilters;
}
