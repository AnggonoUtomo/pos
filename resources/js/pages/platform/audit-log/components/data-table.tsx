import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Eye, History } from 'lucide-react';
import { auditDate, type AuditRow } from '../types';

export function DataTable({ rows, onOpen, disabled }: { rows: AuditRow[]; onOpen: (row: AuditRow) => void; disabled: boolean }) {
    return (
        <div className="min-w-0 overflow-x-auto rounded-md border border-[var(--app-border)] bg-[var(--app-panel)]">
            <table
                className="workspace-table w-full min-w-[850px] text-sm [&_td]:px-4 [&_td]:py-3 [&_th]:px-4 [&_th]:py-3"
                aria-label="Daftar audit log"
            >
                <thead className="text-left">
                    <tr>
                        {['Waktu (WIB)', 'Pengguna', 'Modul', 'Tindakan', 'Objek', 'Detail'].map((title) => (
                            <th key={title} scope="col">
                                {title}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.id} className="align-top">
                            <td className="whitespace-nowrap tabular-nums">
                                {auditDate(row.occurred_at)}
                                <span className="text-muted-foreground mt-1 block text-xs">#{row.id}</span>
                            </td>
                            <td className="max-w-44 break-words">{row.actor_name}</td>
                            <td className="max-w-44 break-words">{row.module_name}</td>
                            <td>
                                <Badge
                                    variant="outline"
                                    className={
                                        row.event?.includes('deleted') || row.event?.includes('failed')
                                            ? 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'
                                            : row.event === 'created' || row.event === 'restored'
                                              ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                              : 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                    }
                                >
                                    {row.event_name}
                                </Badge>
                            </td>
                            <td className="max-w-52 break-all">
                                {row.reference ?? '-'}
                                {row.subject_id && row.reference !== row.subject_id && (
                                    <span className="text-muted-foreground mt-1 block font-mono text-xs">{row.subject_id}</span>
                                )}
                            </td>
                            <td>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    disabled={disabled}
                                    title="Detail aktivitas"
                                    aria-label={`Detail aktivitas ${row.id}`}
                                    onClick={() => onOpen(row)}
                                >
                                    <Eye className="text-sky-600 dark:text-sky-300" />
                                </Button>
                            </td>
                        </tr>
                    ))}
                    {rows.length === 0 && (
                        <tr>
                            <td colSpan={6} className="text-muted-foreground h-32 text-center">
                                <History aria-hidden="true" className="mx-auto mb-2 size-6 text-sky-500" />
                                Tidak ada aktivitas yang sesuai.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
