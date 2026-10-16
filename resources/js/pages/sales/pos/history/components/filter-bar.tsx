import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RotateCcw, Search } from 'lucide-react';
import type { RefObject } from 'react';
import type { HistoryFilters, HistoryOption } from '../types';

export function FilterBar({
    draft,
    onChange,
    onApply,
    onReset,
    loading,
    warehouseOptions,
    customerOptions,
    searchRef,
}: {
    draft: HistoryFilters;
    onChange: (patch: Partial<HistoryFilters>) => void;
    onApply: () => void;
    onReset: () => void;
    loading: boolean;
    warehouseOptions: HistoryOption[];
    customerOptions: HistoryOption[];
    searchRef: RefObject<HTMLInputElement | null>;
}) {
    return (
        <form
            className="grid min-w-0 grid-cols-2 items-end gap-3 xl:grid-cols-6"
            onSubmit={(event) => {
                event.preventDefault();
                onApply();
            }}
        >
            <div className="col-span-2 min-w-0 space-y-1.5">
                <Label htmlFor="invoice-search">Nomor invoice</Label>
                <Input
                    ref={searchRef}
                    id="invoice-search"
                    value={draft.search}
                    maxLength={150}
                    placeholder="POS-..."
                    onChange={(event) => onChange({ search: event.target.value })}
                />
            </div>
            <div className="min-w-0 space-y-1.5">
                <Label htmlFor="date-from">Tanggal awal (WIB)</Label>
                <Input
                    id="date-from"
                    type="date"
                    min="1900-01-01"
                    max="9998-12-31"
                    value={draft.date_from ?? ''}
                    onChange={(event) => onChange({ date_from: event.target.value || null })}
                />
            </div>
            <div className="min-w-0 space-y-1.5">
                <Label htmlFor="date-to">Tanggal akhir (WIB)</Label>
                <Input
                    id="date-to"
                    type="date"
                    min="1900-01-01"
                    max="9998-12-31"
                    value={draft.date_to ?? ''}
                    onChange={(event) => onChange({ date_to: event.target.value || null })}
                />
            </div>
            {(
                [
                    { key: 'warehouse_id', label: 'Gudang', options: warehouseOptions },
                    { key: 'customer_id', label: 'Pelanggan', options: customerOptions },
                ] as const
            ).map(({ key, label, options }) => (
                <div key={key} className="min-w-0 space-y-1.5">
                    <Label htmlFor={`history-${key}`}>{label}</Label>
                    <Select value={draft[key] ?? 'all'} onValueChange={(value) => onChange({ [key]: value === 'all' ? null : value })}>
                        <SelectTrigger id={`history-${key}`} className="w-full min-w-0 [&>span]:truncate">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua {label.toLowerCase()}</SelectItem>
                            {draft[key] && !options.some((option) => option.id === draft[key]) && (
                                <SelectItem value={draft[key]!}>Pilihan tidak tersedia</SelectItem>
                            )}
                            {options.map((option) => (
                                <SelectItem key={option.id} value={option.id}>
                                    {option.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            ))}
            <div className="col-span-2 flex flex-wrap gap-2 xl:col-span-6">
                <Button type="submit" disabled={loading}>
                    <Search className="text-sky-300" />
                    Terapkan
                </Button>
                <Button type="button" variant="outline" disabled={loading} onClick={onReset}>
                    <RotateCcw className="text-amber-600 dark:text-amber-300" />
                    Reset filter
                </Button>
            </div>
        </form>
    );
}
