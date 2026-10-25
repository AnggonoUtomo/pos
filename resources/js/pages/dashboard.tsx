import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { WorkspaceShortcutDropdown, type WorkspaceShortcut } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/pages/reporting/operational-reports/lib/format';
import { type OperationalReport } from '@/pages/reporting/operational-reports/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDownLeft, ArrowUpRight, BarChart3, Boxes, PackageCheck, ReceiptText, RefreshCw, ShoppingCart } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

type DashboardProps = {
    summary: null | {
        date: string;
        sales: { count: number; total: string };
        purchases: OperationalReport['purchases'];
        sales_returns: OperationalReport['sales_returns'];
        purchase_returns: OperationalReport['purchase_returns'];
        payments: OperationalReport['payments'];
        stock: { count: number; total_value: string };
    };
    recent_sales: null | { id: string; number: string; customer_name: string; warehouse_name: string; total: string; posted_at: string }[];
    sales_scope: 'all' | 'own' | null;
    generated_at: string;
};

const dateTime = (value: string) => new Date(value).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' });

export default function Dashboard({ summary, recent_sales, sales_scope, generated_at }: DashboardProps) {
    const { can } = usePermission();
    const canOpenPos = can('sales.pos.invoices.create');
    const [refreshing, setRefreshing] = useState(false);
    const refresh = useCallback(() => {
        if (refreshing) return;
        setRefreshing(true);
        router.reload({
            only: ['summary', 'recent_sales', 'sales_scope', 'generated_at'],
            onError: () => toast.error('Ringkasan gagal dimuat.'),
            onFinish: () => setRefreshing(false),
        });
    }, [refreshing]);
    useEffect(() => {
        const handleKey = (event: KeyboardEvent) => {
            if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.repeat) return;
            if (event.key.toLowerCase() === 'r') {
                event.preventDefault();
                refresh();
            }
            if (event.key.toLowerCase() === 'p' && canOpenPos) {
                event.preventDefault();
                router.visit('/pos');
            }
        };
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, [refresh, canOpenPos]);
    const shortcuts: WorkspaceShortcut[] = [
        { label: 'Command palette', description: 'Navigasi global', keys: ['Ctrl/Cmd', 'K'] },
        { label: 'Muat ulang ringkasan', description: 'Perbarui data', keys: ['Alt', 'R'] },
        ...(canOpenPos ? [{ label: 'Buka POS', description: 'Layar kasir', keys: ['Alt', 'P'] }] : []),
    ];
    const metrics = summary
        ? [
              {
                  label: 'Penjualan hari ini',
                  value: summary.sales.total,
                  detail: `${summary.sales.count} nota lunas · sebelum retur`,
                  icon: ReceiptText,
                  tone: 'text-emerald-600 dark:text-emerald-300',
              },
              {
                  label: 'Pembelian diterima hari ini',
                  value: summary.purchases.total_value,
                  detail: `${summary.purchases.count} dokumen`,
                  icon: PackageCheck,
                  tone: 'text-sky-600 dark:text-sky-300',
              },
              {
                  label: 'Pembayaran masuk hari ini',
                  value: summary.payments.incoming,
                  detail: 'Seluruh gudang · termasuk uang kembali supplier',
                  icon: ArrowDownLeft,
                  tone: 'text-teal-600 dark:text-teal-300',
              },
              {
                  label: 'Pembayaran keluar hari ini',
                  value: summary.payments.outgoing,
                  detail: 'Seluruh gudang · termasuk refund pelanggan',
                  icon: ArrowUpRight,
                  tone: 'text-rose-600 dark:text-rose-300',
              },
          ]
        : [];

    return (
        <AppLayout breadcrumbs={[{ title: 'Dasbor', href: '/dashboard' }]}>
            <Head title="Dasbor" />
            <WorkspaceShortcutDropdown title="Dasbor" shortcuts={shortcuts} />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-5 lg:px-6" aria-busy={refreshing}>
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-primary text-2xl font-semibold">Dasbor Operasional</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Diperbarui {dateTime(generated_at)} WIB</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {canOpenPos && (
                            <Button asChild variant="outline" size="sm">
                                <Link href="/pos">
                                    <ShoppingCart className="size-4 text-sky-600 dark:text-sky-300" />
                                    Buka POS
                                </Link>
                            </Button>
                        )}
                        {summary && (
                            <Button asChild variant="outline" size="sm">
                                <Link href="/reports/operations">
                                    <BarChart3 className="size-4 text-violet-600 dark:text-violet-300" />
                                    Laporan operasional
                                </Link>
                            </Button>
                        )}
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button variant="outline" size="icon" aria-label="Muat ulang ringkasan" disabled={refreshing} onClick={refresh}>
                                    <RefreshCw className={`size-4 text-amber-600 dark:text-amber-300 ${refreshing ? 'animate-spin' : ''}`} />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Muat ulang ringkasan</TooltipContent>
                        </Tooltip>
                    </div>
                </header>

                {summary ? (
                    <>
                        <div className="text-muted-foreground flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                            <span>
                                {new Date(`${summary.date}T00:00:00+07:00`).toLocaleDateString('id-ID', {
                                    timeZone: 'Asia/Jakarta',
                                    dateStyle: 'long',
                                })}{' '}
                                · Semua gudang
                            </span>
                            <span>Arus pembayaran bukan saldo rekening.</span>
                        </div>
                        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan hari ini">
                            {metrics.map((metric) => (
                                <Card key={metric.label} className="min-w-0 border-[var(--app-border)] bg-[var(--app-panel)]">
                                    <CardContent className="space-y-2 p-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <h2 className="text-muted-foreground text-sm">{metric.label}</h2>
                                            <metric.icon className={`size-5 shrink-0 ${metric.tone}`} />
                                        </div>
                                        <p className="text-xl font-semibold [overflow-wrap:anywhere] break-words">{money(metric.value)}</p>
                                        <p className="text-muted-foreground text-xs">{metric.detail}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </section>
                        <section className="grid gap-6 border-y py-5 md:grid-cols-2 xl:grid-cols-4" aria-label="Stok terkini dan retur hari ini">
                            <div className="min-w-0">
                                <h2 className="text-muted-foreground flex items-center gap-2 text-sm">
                                    <Boxes className="size-4 text-amber-600 dark:text-amber-300" />
                                    Nilai persediaan terkini
                                </h2>
                                <p className="mt-2 font-semibold [overflow-wrap:anywhere] break-words">{money(summary.stock.total_value)}</p>
                                <p className="text-muted-foreground mt-1 text-xs">{summary.stock.count} posisi barang/gudang</p>
                            </div>
                            <div className="min-w-0">
                                <h2 className="text-muted-foreground text-sm">Selisih pembayaran hari ini</h2>
                                <p className="mt-2 font-semibold [overflow-wrap:anywhere] break-words">{money(summary.payments.net_movement)}</p>
                                <p className="text-muted-foreground mt-1 text-xs">Mutasi masuk − keluar, bukan laba</p>
                            </div>
                            <div className="min-w-0">
                                <h2 className="text-muted-foreground text-sm">Retur penjualan diterima hari ini</h2>
                                <p className="mt-2 font-semibold">{summary.sales_returns.count} dokumen</p>
                                <p className="text-muted-foreground mt-1 text-xs break-words">
                                    Refund selesai {money(summary.sales_returns.settled_total)}
                                </p>
                            </div>
                            <div className="min-w-0">
                                <h2 className="text-muted-foreground text-sm">Retur supplier dikirim hari ini</h2>
                                <p className="mt-2 font-semibold">{summary.purchase_returns.count} dokumen</p>
                                <p className="text-muted-foreground mt-1 text-xs break-words">
                                    Uang kembali {money(summary.purchase_returns.settled_total)}
                                </p>
                            </div>
                        </section>
                    </>
                ) : (
                    <p className="text-muted-foreground border-y py-5 text-sm">Ringkasan toko memerlukan izin Laporan Operasional.</p>
                )}

                {recent_sales !== null && (
                    <section className="min-w-0 space-y-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-base font-semibold">Penjualan terbaru{sales_scope === 'own' ? ' milik Anda' : ''}</h2>
                            <Button asChild variant="outline" size="sm">
                                <Link href="/sales/invoices">
                                    <ReceiptText className="size-4 text-emerald-600 dark:text-emerald-300" />
                                    Riwayat penjualan
                                </Link>
                            </Button>
                        </div>
                        <div className="overflow-x-auto border border-[var(--app-border)]">
                            <table className="w-full min-w-[680px] text-sm">
                                <thead className="text-muted-foreground bg-[var(--app-panel-strong)]">
                                    <tr>
                                        {['Nomor / Waktu WIB', 'Pelanggan', 'Gudang', 'Total', 'Status'].map((label) => (
                                            <th key={label} className={`px-4 py-3 font-medium ${label === 'Total' ? 'text-right' : 'text-left'}`}>
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {recent_sales.map((sale) => (
                                        <tr key={sale.id} className="border-t">
                                            <td className="px-4 py-3">
                                                <Link
                                                href={`/sales/invoices?search=${encodeURIComponent(sale.number)}`}
                                                    className="text-primary font-medium underline-offset-4 hover:underline"
                                                >
                                                    {sale.number}
                                                </Link>
                                                <p className="text-muted-foreground mt-1 text-xs">{dateTime(sale.posted_at)}</p>
                                            </td>
                                            <td className="px-4 py-3">{sale.customer_name}</td>
                                            <td className="px-4 py-3">{sale.warehouse_name}</td>
                                            <td className="px-4 py-3 text-right font-medium whitespace-nowrap">{money(sale.total)}</td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className="border-emerald-200 bg-emerald-500/10 text-emerald-700 dark:border-emerald-900 dark:text-emerald-300"
                                                >
                                                    Lunas
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                    {!recent_sales.length && (
                                        <tr>
                                            <td colSpan={5} className="text-muted-foreground px-4 py-10 text-center">
                                                Belum ada penjualan{sales_scope === 'own' ? ' milik Anda' : ''}.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
