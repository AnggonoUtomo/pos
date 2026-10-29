import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Eye } from 'lucide-react';
import { money } from '../../lib/decimal';
import type { InvoiceRow } from '../types';

const date = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' });

export function DataTable({ rows, onOpen, loading }: { rows: InvoiceRow[]; onOpen: (id: string) => void; loading: boolean }) {
    return (
        <div className="min-w-0 overflow-x-auto rounded-md border bg-[var(--app-panel)]">
            <table className="workspace-table w-full min-w-[850px] text-sm" aria-label="Daftar penjualan">
                <thead className="bg-muted/40 text-left">
                    <tr>
                        {['Invoice / Waktu (WIB)', 'Pelanggan', 'Gudang', 'Status', 'Total', ''].map((label, index) => (
                            <th key={index} className={`px-4 py-3 font-medium ${index === 4 ? 'text-right' : ''}`}>
                                {label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.id} className="border-t align-top" data-testid="invoice-row">
                            <td className="max-w-72 px-4 py-3">
                                <div className="font-mono text-xs break-all">{row.number}</div>
                                <div className="text-muted-foreground mt-1 text-xs">{date.format(new Date(row.posted_at))}</div>
                            </td>
                            <td className="max-w-56 px-4 py-3 break-words">
                                {row.customer_name}
                                <div className="text-muted-foreground mt-1 text-xs">{row.customer_level_name}</div>
                            </td>
                            <td className="max-w-48 px-4 py-3 break-words">{row.warehouse_name}</td>
                            <td className="px-4 py-3">
                                <Badge variant="outline" className="border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                    Lunas
                                </Badge>
                            </td>
                            <td className="max-w-56 px-4 py-3 text-right font-medium break-all">{money(row.total)}</td>
                            <td className="px-3 py-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    disabled={loading}
                                    title="Lihat bukti"
                                    aria-label={`Lihat ${row.number}`}
                                    onClick={() => onOpen(row.id)}
                                >
                                    <Eye className="text-sky-600 dark:text-sky-300" />
                                </Button>
                            </td>
                        </tr>
                    ))}
                    {!rows.length && (
                        <tr>
                            <td colSpan={6} className="text-muted-foreground px-4 py-10 text-center" role="status">
                                Tidak ada penjualan ditemukan.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
