import { Button } from '@/components/ui/button';
import { Undo2 } from 'lucide-react';
import { dateLabel, kindLabel, money } from '../lib/money';
import type { CashRow } from '../types';

export function StatementTable({
    rows,
    canReverse,
    disabled,
    onReverse,
}: {
    rows: CashRow[];
    canReverse: boolean;
    disabled: boolean;
    onReverse: (row: CashRow) => void;
}) {
    return (
        <div className="min-w-0 overflow-x-auto rounded-md border">
            <table className="workspace-table w-full min-w-[860px] text-sm">
                <thead className="bg-muted/40 text-muted-foreground border-b">
                    <tr>
                        {['Waktu (WIB)', 'Referensi / keterangan', 'Masuk', 'Keluar', 'Saldo', 'Operasi'].map((label, index) => (
                            <th key={label} scope="col" className={`px-3 py-3 font-medium ${index > 1 ? 'text-right' : 'text-left'}`}>
                                {label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {rows.map((row) => (
                        <tr key={row.kind + row.row_id} className="hover:bg-muted/30">
                            <td className="px-3 py-3 text-xs whitespace-nowrap">{dateLabel(row.occurred_at)}</td>
                            <td className="max-w-80 min-w-48 px-3 py-3 whitespace-normal">
                                <p className="font-medium break-all">{row.number}</p>
                                <p className="text-muted-foreground text-xs">{kindLabel[row.kind] ?? row.kind}</p>
                                <p className="text-sm break-words">{row.description}</p>
                            </td>
                            <td className="px-3 py-3 text-right whitespace-nowrap text-emerald-700 dark:text-emerald-300">
                                {row.direction === 'incoming' ? money(row.amount) : '-'}
                            </td>
                            <td className="px-3 py-3 text-right whitespace-nowrap text-rose-700 dark:text-rose-300">
                                {row.direction === 'outgoing' ? money(row.amount) : '-'}
                            </td>
                            <td className="px-3 py-3 text-right font-medium whitespace-nowrap">{money(row.balance)}</td>
                            <td className="px-3 py-2 text-right">
                                {canReverse && row.reversible_id && (
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        disabled={disabled}
                                        aria-label={`Balik ${row.number}`}
                                        title="Balik mutasi"
                                        onClick={() => onReverse(row)}
                                    >
                                        <Undo2 className="text-amber-600 dark:text-amber-300" />
                                    </Button>
                                )}
                            </td>
                        </tr>
                    ))}
                    {!rows.length && (
                        <tr>
                            <td colSpan={6} className="text-muted-foreground py-10 text-center">
                                Belum ada mutasi pada periode ini.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
