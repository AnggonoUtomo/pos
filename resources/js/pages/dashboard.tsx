import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, Boxes, CreditCard, PackageSearch, ReceiptText, ShoppingCart, Warehouse } from 'lucide-react';
import { type ReactNode } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dasbor',
        href: '/dashboard',
    },
];

const summaryCards = [
    {
        title: 'Penjualan Hari Ini',
        value: 'Rp 18.420.000',
        note: '128 transaksi selesai',
        trend: '+12,4%',
        tone: 'emerald',
        icon: ReceiptText,
    },
    {
        title: 'Transaksi POS',
        value: '24 antre',
        note: '3 draft payment',
        trend: 'Live',
        tone: 'blue',
        icon: ShoppingCart,
    },
    {
        title: 'Stok Menipis',
        value: '17 item',
        note: 'Butuh pembelian ulang',
        trend: 'Cek',
        tone: 'amber',
        icon: Boxes,
    },
    {
        title: 'Retur Pending',
        value: '5 dokumen',
        note: 'Menunggu review admin',
        trend: '-2',
        tone: 'rose',
        icon: ArrowDownRight,
    },
];

const warehouseStatus = [
    { name: 'Gudang Utama', value: '68%', detail: 'FIFO layer sehat', tone: 'emerald' },
    { name: 'Etalase Toko', value: '42%', detail: '7 item stok tipis', tone: 'amber' },
    { name: 'Gudang Retur', value: '18%', detail: 'Butuh adjustment', tone: 'rose' },
];

const recentTransactions = [
    { code: 'POS-260913-0128', customer: 'Umum', warehouse: 'Etalase Toko', total: 'Rp 428.000', status: 'Lunas', tone: 'emerald' },
    { code: 'POS-260913-0127', customer: 'Grosir A', warehouse: 'Gudang Utama', total: 'Rp 2.340.000', status: 'Multi Payment', tone: 'blue' },
    { code: 'POS-260913-0126', customer: 'Reseller Timur', warehouse: 'Gudang Utama', total: 'Rp 1.190.000', status: 'Draft', tone: 'amber' },
    { code: 'RET-260913-0005', customer: 'Umum', warehouse: 'Gudang Retur', total: 'Rp 86.000', status: 'Review', tone: 'rose' },
];

const operations = [
    { title: 'Harga level pelanggan', value: '3 level aktif', icon: CreditCard },
    { title: 'Multi satuan', value: 'Base unit terkunci', icon: PackageSearch },
    { title: 'Multi gudang', value: 'Pilih gudang per transaksi', icon: Warehouse },
];

const toneClasses: Record<string, string> = {
    emerald: 'border-emerald-200 bg-emerald-500/10 text-emerald-700 dark:border-emerald-900 dark:text-emerald-300',
    blue: 'border-sky-200 bg-sky-500/10 text-sky-700 dark:border-sky-900 dark:text-sky-300',
    amber: 'border-amber-200 bg-amber-500/10 text-amber-700 dark:border-amber-900 dark:text-amber-300',
    rose: 'border-rose-200 bg-rose-500/10 text-rose-700 dark:border-rose-900 dark:text-rose-300',
};

function ToneBadge({ children, tone }: { children: ReactNode; tone: string }) {
    return (
        <Badge variant="outline" className={cn('shrink-0', toneClasses[tone])}>
            {children}
        </Badge>
    );
}

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasbor" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-5 px-4 py-5 lg:px-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-normal">Dasbor Operasional</h1>
                            <ToneBadge tone="emerald">Shift aktif</ToneBadge>
                        </div>
                        <p className="text-muted-foreground mt-1 text-sm">Ringkasan awal untuk penjualan, gudang, harga level pelanggan, dan Finance Lite.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href="/pos" prefetch>
                                <ShoppingCart className="size-4" />
                                Buka POS
                            </Link>
                        </Button>
                        <Button size="sm" disabled>
                            <ReceiptText className="size-4" />
                            Rekap Hari Ini
                        </Button>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {summaryCards.map((item) => (
                        <Card key={item.title}>
                            <CardContent className="p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-muted-foreground text-sm">{item.title}</p>
                                        <p className="mt-2 text-2xl font-semibold tracking-normal">{item.value}</p>
                                        <p className="text-muted-foreground mt-1 text-xs">{item.note}</p>
                                    </div>
                                    <div className={cn('rounded-lg border p-2', toneClasses[item.tone])}>
                                        <item.icon className="size-4" />
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center justify-between">
                                    <ToneBadge tone={item.tone}>{item.trend}</ToneBadge>
                                    <ArrowUpRight className="text-muted-foreground size-4" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_380px]">
                    <Card>
                        <CardHeader className="flex-row items-center justify-between space-y-0 p-4">
                            <div>
                                <CardTitle className="text-base">Transaksi Terbaru</CardTitle>
                                <p className="text-muted-foreground text-sm">Snapshot operasional untuk baseline UI.</p>
                            </div>
                            <ToneBadge tone="blue">Realtime mock</ToneBadge>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[680px] text-sm">
                                    <thead className="bg-muted/40 text-muted-foreground">
                                        <tr className="border-y">
                                            <th className="px-4 py-3 text-left font-medium">Nomor</th>
                                            <th className="px-4 py-3 text-left font-medium">Pelanggan</th>
                                            <th className="px-4 py-3 text-left font-medium">Gudang</th>
                                            <th className="px-4 py-3 text-right font-medium">Total</th>
                                            <th className="px-4 py-3 text-right font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentTransactions.map((transaction) => (
                                            <tr key={transaction.code} className="border-b last:border-b-0">
                                                <td className="px-4 py-3 font-medium">{transaction.code}</td>
                                                <td className="px-4 py-3">{transaction.customer}</td>
                                                <td className="text-muted-foreground px-4 py-3">{transaction.warehouse}</td>
                                                <td className="px-4 py-3 text-right font-medium">{transaction.total}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <ToneBadge tone={transaction.tone}>{transaction.status}</ToneBadge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4">
                        <Card>
                            <CardHeader className="p-4">
                                <CardTitle className="text-base">Status Gudang</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 p-4 pt-0">
                                {warehouseStatus.map((warehouse) => (
                                    <div key={warehouse.name} className="space-y-2">
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium">{warehouse.name}</p>
                                                <p className="text-muted-foreground text-xs">{warehouse.detail}</p>
                                            </div>
                                            <ToneBadge tone={warehouse.tone}>{warehouse.value}</ToneBadge>
                                        </div>
                                        <div className="bg-muted h-2 overflow-hidden rounded-full">
                                            <div className={cn('h-full rounded-full', toneClasses[warehouse.tone].split(' ')[1])} style={{ width: warehouse.value }} />
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="p-4">
                                <CardTitle className="text-base">Baseline Yang Dijaga</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 p-4 pt-0">
                                {operations.map((operation, index) => (
                                    <div key={operation.title}>
                                        <div className="flex items-center gap-3">
                                            <div className="bg-muted flex size-9 items-center justify-center rounded-lg">
                                                <operation.icon className="text-muted-foreground size-4" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium">{operation.title}</p>
                                                <p className="text-muted-foreground text-xs">{operation.value}</p>
                                            </div>
                                        </div>
                                        {index < operations.length - 1 && <Separator className="mt-3" />}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
