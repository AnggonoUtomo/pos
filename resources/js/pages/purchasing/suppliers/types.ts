import type { MasterFilters, MasterRecord, MasterRecords } from '@/components/shared/master-workspace';

export interface SupplierRow extends MasterRecord {
    code: string;
    phone: string | null;
    email: string | null;
    address: string | null;
}

export interface SupplierPageProps {
    records: MasterRecords<SupplierRow>;
    filters: MasterFilters;
}
