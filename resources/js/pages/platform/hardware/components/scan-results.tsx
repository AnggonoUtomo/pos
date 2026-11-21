import { Badge } from '@/components/ui/badge';
import { CircleCheck, CircleX, ScanBarcode } from 'lucide-react';
import type { ScanResult } from '../types';

export function ScanResults({ results }: { results: ScanResult[] }) {
    return (
        <section className="min-w-0 space-y-4" aria-label="Hasil uji scanner">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-base font-semibold">Hasil pemindaian</h2>
                <span className="text-muted-foreground text-sm">{results.length} dari maksimal 20 hasil terakhir</span>
            </div>
            {!results.length ? (
                <div className="flex min-h-44 flex-col items-center justify-center gap-3 border-y border-dashed border-[var(--app-border)] p-6 text-center">
                    <ScanBarcode className="size-8 text-sky-600 dark:text-sky-300" />
                    <p className="text-muted-foreground text-sm">Belum ada hasil pemindaian</p>
                </div>
            ) : (
                <div className="overflow-x-auto border-y border-[var(--app-border)]">
                    <table className="w-full min-w-[560px] text-sm">
                        <thead className="bg-[var(--app-panel-strong)] text-left">
                            <tr>
                                {['Kode input', 'Barang / SKU', 'Satuan', 'Hasil'].map((title) => (
                                    <th key={title} className="px-4 py-3 font-medium">
                                        {title}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {results.map((row) => (
                                <tr
                                    key={row.id}
                                    data-testid="scan-result"
                                    className="border-t border-[var(--app-border)] align-top odd:bg-[var(--app-panel)] even:bg-[var(--app-panel-strong)]"
                                >
                                    <td className="max-w-48 px-4 py-3 font-mono text-xs break-all">{row.code}</td>
                                    <td className="max-w-72 px-4 py-3 break-words">
                                        {row.identity?.name ?? '-'}
                                        <span className="text-muted-foreground mt-1 block text-xs">{row.identity?.sku}</span>
                                    </td>
                                    <td className="px-4 py-3">{row.identity?.unit_name ?? '-'}</td>
                                    <td className="max-w-72 px-4 py-3">
                                        <Badge
                                            variant="outline"
                                            className={
                                                row.status === 'found'
                                                    ? 'gap-1 text-emerald-700 dark:text-emerald-300'
                                                    : 'gap-1 text-rose-700 dark:text-rose-300'
                                            }
                                        >
                                            {row.status === 'found' ? <CircleCheck className="size-3" /> : <CircleX className="size-3" />}
                                            {row.status === 'found' ? 'Dikenali' : 'Belum berhasil'}
                                        </Badge>
                                        <p className="text-muted-foreground mt-2 text-xs break-words">{row.message}</p>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}
