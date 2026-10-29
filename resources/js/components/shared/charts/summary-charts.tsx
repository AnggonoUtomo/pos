import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { ArrowDownLeft, ArrowUpRight, BarChart3, CircleDot } from 'lucide-react';
import { useId, type CSSProperties } from 'react';
import { chartRatios } from './ratios';

export type ChartDatum = { label: string; value: string; display: string; detail?: string; color: string };

export function ComparisonChart({ title, description, series }: { title: string; description: string; series: ChartDatum[] }) {
    const id = useId();
    const ratios = chartRatios(series.map((row) => row.value));
    const empty = ratios.every((value) => value === 0);
    return (
        <Card
            className="flex min-w-0 flex-col gap-0 overflow-hidden border-[var(--app-border)] bg-[var(--app-panel)] py-0 shadow-none"
            data-testid="comparison-chart"
        >
            <CardHeader className="flex flex-row items-start justify-between gap-3 border-b border-[var(--app-border)] p-5">
                <div className="min-w-0 space-y-1">
                    <CardTitle id={id} role="heading" aria-level={2} className="text-base tracking-normal">
                        {title}
                    </CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
                <BarChart3 className="mt-1 size-5 shrink-0 text-sky-600 dark:text-sky-300" />
            </CardHeader>
            <CardContent className="space-y-5 p-5" role="group" aria-labelledby={id}>
                {series.map((row, index) => (
                    <Tooltip key={row.label}>
                        <TooltipTrigger asChild>
                            <button
                                type="button"
                                className="chart-row focus-visible:ring-ring block w-full rounded-sm text-left outline-none focus-visible:ring-2 focus-visible:ring-offset-4"
                                aria-label={`${row.label}: ${row.display}${row.detail ? `, ${row.detail}` : ''}`}
                            >
                                <span className="mb-2 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 text-sm">
                                    <span className="inline-flex min-w-0 items-center gap-2">
                                        <span className="size-2 shrink-0 rounded-full" style={{ backgroundColor: row.color }} />
                                        {row.label}
                                    </span>
                                    <strong className="[overflow-wrap:anywhere] tabular-nums">{row.display}</strong>
                                </span>
                                <span className="chart-track relative block h-3 overflow-hidden rounded-sm">
                                    <span
                                        data-chart-bar
                                        className="chart-bar block h-full origin-left rounded-sm"
                                        style={{ width: `${ratios[index]}%`, backgroundColor: row.color, animationDelay: `${index * 70}ms` }}
                                    />
                                </span>
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>
                                {row.label}: {row.display}
                            </p>
                            {row.detail && <p>{row.detail}</p>}
                        </TooltipContent>
                    </Tooltip>
                ))}
                {empty && (
                    <p className="text-muted-foreground text-xs" role="status">
                        Belum ada nilai transaksi pada periode ini.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

export function FlowChart({
    title,
    description,
    incoming,
    outgoing,
    net,
}: {
    title: string;
    description: string;
    incoming: ChartDatum;
    outgoing: ChartDatum;
    net: string;
}) {
    const id = useId();
    const ratios = chartRatios([incoming.value, outgoing.value], 'sum');
    const empty = ratios.every((value) => value === 0);
    return (
        <Card
            className="flex min-w-0 flex-col gap-0 overflow-hidden border-[var(--app-border)] bg-[var(--app-panel)] py-0 shadow-none"
            data-testid="flow-chart"
        >
            <CardHeader className="flex flex-row items-start justify-between gap-3 border-b border-[var(--app-border)] p-5">
                <div className="min-w-0 space-y-1">
                    <CardTitle id={id} role="heading" aria-level={2} className="text-base tracking-normal">
                        {title}
                    </CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
                <CircleDot className="mt-1 size-5 shrink-0 text-emerald-600 dark:text-emerald-300" />
            </CardHeader>
            <CardContent className="flex flex-1 flex-col justify-between gap-5 p-5">
                <div className="flex flex-wrap items-center gap-6">
                    <div
                        className="relative mx-auto size-40 shrink-0"
                        role="img"
                        aria-label={`${incoming.label}: ${incoming.display}; ${outgoing.label}: ${outgoing.display}`}
                    >
                        <svg viewBox="0 0 160 160" className="size-full -rotate-90" aria-hidden="true">
                            <circle className="chart-ring-track" cx="80" cy="80" r="60" fill="none" strokeWidth="16" />
                            {!empty &&
                                [incoming, outgoing].map((row, index) => (
                                    <circle
                                        key={row.label}
                                        data-chart-arc
                                        className="chart-arc"
                                        cx="80"
                                        cy="80"
                                        r="60"
                                        fill="none"
                                        pathLength="100"
                                        stroke={row.color}
                                        strokeWidth="16"
                                        strokeDasharray={`${ratios[index]} ${100 - ratios[index]}`}
                                        strokeDashoffset={index === 0 ? '0' : String(-ratios[0])}
                                        style={{ '--chart-circumference': 100 } as CSSProperties}
                                    />
                                ))}
                        </svg>
                        <span className="text-muted-foreground pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1 text-center text-xs">
                            <CircleDot className="mb-1 size-5 text-[var(--chart-2)]" />
                            <span>{empty ? 'Belum ada' : 'Masuk / keluar'}</span>
                            <span>{empty ? 'pembayaran' : 'Periode terpilih'}</span>
                        </span>
                    </div>
                    <dl className="min-w-0 flex-1 space-y-5">
                        {[incoming, outgoing].map((row, index) => (
                            <div key={row.label} className="min-w-0">
                                <dt className="text-muted-foreground mb-1 flex items-center gap-2 text-xs">
                                    {index === 0 ? (
                                        <ArrowDownLeft className="size-4" style={{ color: row.color }} />
                                    ) : (
                                        <ArrowUpRight className="size-4" style={{ color: row.color }} />
                                    )}
                                    {row.label}
                                </dt>
                                <dd className="text-base font-semibold [overflow-wrap:anywhere] tabular-nums">{row.display}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
                <div className="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--app-border)] pt-4 text-sm">
                    <span className="text-muted-foreground">Selisih mutasi, bukan saldo</span>
                    <strong className="[overflow-wrap:anywhere] tabular-nums">{net}</strong>
                </div>
            </CardContent>
        </Card>
    );
}
