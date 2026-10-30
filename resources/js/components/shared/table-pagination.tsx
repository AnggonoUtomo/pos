import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router, usePage } from '@inertiajs/react';
import { ChevronFirst, ChevronLast, ChevronLeft, ChevronRight } from 'lucide-react';
import { toast } from 'sonner';
import { pageNumbers, tablePageSizes, type TableMeta } from './table-pagination-model';

export function TablePagination({
    meta,
    disabled = false,
    onChange,
}: {
    meta: TableMeta;
    disabled?: boolean;
    onChange?: (value: { page: number; perPage: number }) => void;
}) {
    const { url } = usePage();
    const change = (page: number, perPage = meta.per_page) => {
        if (disabled) return;
        if (onChange) {
            onChange({ page, perPage });
            return;
        }
        const target = new URL(url, window.location.origin);
        target.searchParams.set('page', String(page));
        target.searchParams.set('per_page', String(perPage));
        router.get(
            target.pathname + target.search,
            {},
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Halaman belum dapat dimuat. Periksa filter atau coba lagi.') },
        );
    };
    const first = meta.total === 0 || meta.current_page > meta.last_page ? 0 : (meta.current_page - 1) * meta.per_page + 1;
    const last = first === 0 ? 0 : Math.min(meta.current_page * meta.per_page, meta.total);
    const format = (value: number) => value.toLocaleString('id-ID');
    const arrows = [
        { label: 'Halaman pertama', page: 1, icon: ChevronFirst, disabled: meta.current_page <= 1 },
        { label: 'Halaman sebelumnya', page: Math.max(1, meta.current_page - 1), icon: ChevronLeft, disabled: meta.current_page <= 1 },
        {
            label: 'Halaman berikutnya',
            page: Math.min(meta.last_page, meta.current_page + 1),
            icon: ChevronRight,
            disabled: meta.current_page >= meta.last_page,
        },
        { label: 'Halaman terakhir', page: meta.last_page, icon: ChevronLast, disabled: meta.current_page >= meta.last_page },
    ];
    const arrow = (index: number) => {
        const item = arrows[index];
        return (
            <Button
                key={item.label}
                type="button"
                variant="outline"
                size="icon"
                className="size-8"
                aria-label={item.label}
                title={item.label}
                disabled={disabled || item.disabled}
                onClick={() => change(item.page)}
            >
                <item.icon className="size-4 text-sky-600 dark:text-sky-300" />
            </Button>
        );
    };
    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--app-border)] pt-4" data-table-pagination>
            <div className="flex flex-wrap items-center gap-3">
                <Select value={String(meta.per_page)} disabled={disabled} onValueChange={(value) => change(1, Number(value))}>
                    <SelectTrigger aria-label="Baris per halaman" className="h-8 w-20">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {tablePageSizes.map((size) => (
                            <SelectItem key={size} value={String(size)}>
                                {size}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <p className="text-muted-foreground text-xs" aria-live="polite">
                    {format(first)}–{format(last)} dari {format(meta.total)} data · Halaman {format(meta.current_page)} dari {format(meta.last_page)}
                </p>
            </div>
            <nav aria-label="Navigasi halaman" className="flex flex-wrap items-center gap-1">
                {arrow(0)}
                {arrow(1)}
                {pageNumbers(meta.current_page, meta.last_page).map((page) =>
                    typeof page === 'number' ? (
                        <Button
                            key={page}
                            type="button"
                            variant={page === meta.current_page ? 'default' : 'outline'}
                            size="icon"
                            className="h-8 w-auto min-w-8 px-2 text-xs"
                            aria-label={`Halaman ${page}`}
                            aria-current={page === meta.current_page ? 'page' : undefined}
                            disabled={disabled || page === meta.current_page}
                            onClick={() => change(page)}
                        >
                            {format(page)}
                        </Button>
                    ) : (
                        <span key={page} className="text-muted-foreground px-1" aria-hidden="true">
                            ...
                        </span>
                    ),
                )}
                {arrow(2)}
                {arrow(3)}
            </nav>
        </div>
    );
}
