import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export interface UnitRow extends MasterRecord {
    code: string;
    symbol: string;
}
export interface UnitPageProps {
    records: MasterRecords<UnitRow>;
    filters: MasterFilters;
}
