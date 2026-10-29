import { ComparisonChart, FlowChart } from '@/components/shared/charts/summary-charts';
import { money } from '../lib/format';

type ChartSummary = {
    sales: { total: string; count: number };
    purchases: { total_value: string; count: number };
    sales_returns: { settled_total: string; count: number };
    purchase_returns: { settled_total: string; count: number };
    payments: { incoming: string; outgoing: string; net_movement: string };
};

export function OperationalCharts({ summary, period }: { summary: ChartSummary; period: string }) {
    return (
        <section className="grid min-w-0 items-stretch gap-5 xl:grid-cols-[1.2fr_1fr]" aria-label="Grafik operasional">
            <ComparisonChart
                title="Komposisi transaksi"
                description={period}
                series={[
                    {
                        label: 'Penjualan lunas',
                        value: summary.sales.total,
                        display: money(summary.sales.total),
                        detail: `${summary.sales.count} nota, sebelum retur`,
                        color: 'var(--chart-1)',
                    },
                    {
                        label: 'Pembelian diterima',
                        value: summary.purchases.total_value,
                        display: money(summary.purchases.total_value),
                        detail: `${summary.purchases.count} dokumen`,
                        color: 'var(--chart-2)',
                    },
                    {
                        label: 'Refund pelanggan selesai',
                        value: summary.sales_returns.settled_total,
                        display: money(summary.sales_returns.settled_total),
                        color: 'var(--chart-4)',
                    },
                    {
                        label: 'Uang kembali supplier',
                        value: summary.purchase_returns.settled_total,
                        display: money(summary.purchase_returns.settled_total),
                        color: 'var(--chart-5)',
                    },
                ]}
            />
            <FlowChart
                title="Arus pembayaran"
                description="Seluruh gudang · bukan laba"
                net={money(summary.payments.net_movement)}
                incoming={{
                    label: 'Pembayaran masuk',
                    value: summary.payments.incoming,
                    display: money(summary.payments.incoming),
                    color: 'var(--chart-2)',
                }}
                outgoing={{
                    label: 'Pembayaran keluar',
                    value: summary.payments.outgoing,
                    display: money(summary.payments.outgoing),
                    color: 'var(--chart-4)',
                }}
            />
        </section>
    );
}
