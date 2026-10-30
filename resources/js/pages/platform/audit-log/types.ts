export type AuditValue = string | boolean | null | string[];
export type AuditRow = {
    id: string;
    occurred_at: string | null;
    actor_name: string;
    module: string | null;
    module_name: string;
    event: string | null;
    event_name: string;
    subject_id: string | null;
    reference: string | null;
};
export type AuditDetail = AuditRow & {
    before: Record<string, AuditValue> | null;
    after: Record<string, AuditValue> | null;
    field_labels: Record<string, string>;
};
export type AuditFilters = {
    search: string;
    module: string | null;
    event: string | null;
    actor_id: string | null;
    date_from: string | null;
    date_to: string | null;
};
export type AuditOption = { id: string; name: string };
export type AuditProps = {
    records: { data: AuditRow[]; meta: { current_page: number; last_page: number; per_page: number; total: number } };
    filters: AuditFilters;
    options: { modules: AuditOption[]; events: AuditOption[]; actors: AuditOption[] };
};

export function auditDate(value: string | null): string {
    if (!value || Number.isNaN(Date.parse(value))) return '-';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(new Date(value));
}
