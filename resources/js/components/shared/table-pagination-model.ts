export const tablePageSizes = [10, 25, 50, 100] as const;
export type TableMeta = { current_page: number; last_page: number; per_page: number; total: number };

export function pageNumbers(current: number, last: number): (number | 'ellipsis-start' | 'ellipsis-end')[] {
    if (last <= 7) return Array.from({ length: Math.max(1, last) }, (_, index) => index + 1);
    const center = Math.min(Math.max(current, 1), last);
    const start = Math.max(2, Math.min(center - 1, last - 4));
    const end = Math.min(last - 1, Math.max(center + 1, 5));
    return [
        1,
        ...(start > 2 ? ['ellipsis-start' as const] : []),
        ...Array.from({ length: end - start + 1 }, (_, i) => start + i),
        ...(end < last - 1 ? ['ellipsis-end' as const] : []),
        last,
    ];
}

export function tablePageParams(): { per_page?: number } {
    const value = Number(new URL(window.location.href).searchParams.get('per_page'));
    return (tablePageSizes as readonly number[]).includes(value) ? { per_page: value } : {};
}
