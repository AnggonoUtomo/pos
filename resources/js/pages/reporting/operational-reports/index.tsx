import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { BarChart3, Filter, LoaderCircle } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { OperationalCharts } from './components/operational-charts';
import { decimal, money } from './lib/format';
import type { OperationalReport } from './types';

export default function OperationalReports(report: OperationalReport) {
    const dateLabel = (date: string) =>
        new Date(`${date}T00:00:00+07:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' });
    const form = useForm(report.filters);
    const formRef = useRef<HTMLFormElement>(null);
    const dateRef = useRef<HTMLInputElement>(null);
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                formRef.current?.requestSubmit();
            }
            if (e.altKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                dateRef.current?.focus();
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, []);
    const metrics = [
        ['Penjualan lunas', money(report.sales.total), `${report.sales.count} nota`],
        ['Pembelian diterima', money(report.purchases.total_value), `${report.purchases.count} dokumen`],
        ['Retur penjualan diterima', `${report.sales_returns.count} dokumen`, `Refund selesai ${money(report.sales_returns.settled_total)}`],
        ['Retur supplier dikirim', `${report.purchase_returns.count} dokumen`, `Uang kembali ${money(report.purchase_returns.settled_total)}`],
    ];
    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan operasional', href: route('reports.operations.index') }]}>
            <Head title="Laporan operasional" />
            <WorkspaceShortcutDropdown
                title="Laporan operasional"
                shortcuts={[
                    { label: 'Periode', description: 'Fokus awal periode', keys: ['Alt', 'F'] },
                    { label: 'Terapkan filter', description: 'Muat laporan', keys: ['Ctrl/Cmd', 'Enter'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-6 p-4 lg:p-6" aria-busy={form.processing}>
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <BarChart3 className="size-5 text-violet-600 dark:text-violet-300" />
                    Laporan operasional
                </h1>
                <form
                    ref={formRef}
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (!form.processing)
                            form.get(route('reports.operations.index'), {
                                preserveState: true,
                                preserveScroll: true,
                                onError: () => toast.error('Filter laporan tidak valid.'),
                            });
                    }}
                >
                    <div className="space-y-2">
                        <Label htmlFor="report-from">Dari tanggal</Label>
                        <Input
                            ref={dateRef}
                            id="report-from"
                            type="date"
                            value={form.data.from}
                            onChange={(e) => form.setData('from', e.target.value)}
                        />
                        <InputError message={form.errors.from} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="report-to">Sampai tanggal</Label>
                        <Input id="report-to" type="date" value={form.data.to} onChange={(e) => form.setData('to', e.target.value)} />
                        <InputError message={form.errors.to} />
                    </div>
                    <div className="space-y-2">
                        <Label>Gudang transaksi/stok</Label>
                        <Select value={form.data.warehouse_id || 'all'} onValueChange={(id) => form.setData('warehouse_id', id === 'all' ? '' : id)}>
                            <SelectTrigger aria-label="Gudang laporan" className="w-60">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua gudang</SelectItem>
                                {report.warehouses.map((row) => (
                                    <SelectItem key={row.id} value={row.id}>
                                        {row.code} - {row.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.warehouse_id} />
                    </div>
                    <Button disabled={form.processing}>
                        {form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Filter className="size-4" />}Terapkan
                    </Button>
                </form>
                <dl className="grid gap-x-6 gap-y-4 border-y py-5 sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map(([label, value, detail]) => (
                        <div key={label} className="min-w-0 space-y-1">
                            <dt className="text-muted-foreground text-sm">{label}</dt>
                            <dd className="text-lg font-semibold break-all">{value}</dd>
                            <dd className="text-muted-foreground text-xs break-words">{detail}</dd>
                        </div>
                    ))}
                </dl>
                <OperationalCharts
                    key={`${report.filters.from}:${report.filters.to}:${report.filters.warehouse_id}:${report.generated_at}`}
                    summary={report}
                    period={`${dateLabel(report.filters.from)} s.d. ${dateLabel(report.filters.to)} · ${report.filters.warehouse_id ? 'gudang terpilih' : 'semua gudang'} · nilai rupiah`}
                />
                <section className="min-w-0 space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-base font-semibold">Stok terkini</h2>
                        <span className="text-sm">Nilai persediaan {money(report.stock.total_value)}</span>
                    </div>
                    <div className="overflow-x-auto border">
                        <table className="workspace-table w-full min-w-[720px] text-sm">
                            <thead className="bg-muted/40">
                                <tr>
                                    {['SKU / Barang', 'Gudang', 'Saldo dasar', 'Nilai persediaan'].map((label) => (
                                        <th key={label} className="p-3 text-left font-medium">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {report.stock.rows.map((row, i) => (
                                    <tr key={i} className="border-t">
                                        <td className="p-3">
                                            <span className="font-mono text-xs">{row.sku}</span>
                                            <div>{row.name}</div>
                                        </td>
                                        <td className="p-3">{row.warehouse}</td>
                                        <td className="p-3">
                                            {decimal(row.quantity)} {row.unit}
                                        </td>
                                        <td className="p-3">{money(row.total_value)}</td>
                                    </tr>
                                ))}
                                {!report.stock.rows.length && (
                                    <tr>
                                        <td colSpan={4} className="text-muted-foreground p-8 text-center">
                                            Belum ada saldo stok.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <p className="text-muted-foreground text-xs">
                        {report.stock.count} posisi barang/gudang{report.stock.truncated ? ' (100 baris pertama)' : ''} /{' '}
                        {new Date(report.generated_at).toLocaleString('id-ID')}
                    </p>
                </section>
            </section>
        </AppLayout>
    );
}
