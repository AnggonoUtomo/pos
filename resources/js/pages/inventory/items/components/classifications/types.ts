import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export type ClassificationKind = 'categories' | 'brands';
export interface ClassificationRow extends MasterRecord {
    code: string;
}
export type ClassificationPageProps = { records: MasterRecords<ClassificationRow>; filters: MasterFilters };
