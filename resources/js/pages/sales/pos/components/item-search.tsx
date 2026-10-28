import { ItemImage } from '@/components/shared/item-image';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, LoaderCircle, PackagePlus, Search } from 'lucide-react';
import { useState } from 'react';
import { hasStock, localDecimal, money } from '../lib/decimal';
import type { PosItem } from '../types';

function CatalogItem({ item, onAdd, visual }: { item: PosItem; onAdd: (item: PosItem, unitId: string) => void; visual: boolean }) {
    const [unitId, setUnitId] = useState(item.units.find((row) => row.id === item.base_unit_id)?.id ?? item.units[0]?.id);
    const unit = item.units.find((row) => row.id === unitId) ?? item.units[0];
    const available = hasStock(item.stock) && unit?.price !== null;
    return (
        <article className="flex min-w-0 flex-col gap-3 rounded-md border border-[var(--app-border)] bg-[var(--app-panel-strong)] p-3">
            <div className={cn('flex min-w-0 gap-3', visual ? 'flex-col' : 'items-center')}>
                <ItemImage src={item.image_url ?? null} name={item.name} className={visual ? 'h-24 w-full rounded-sm' : 'size-16'} />
                <div className="min-w-0">
                    <h3 className={cn('text-sm font-semibold break-words', visual && 'line-clamp-2 min-h-10')} title={item.name}>
                        {item.name}
                    </h3>
                    <p className="text-muted-foreground text-xs break-all">{item.sku}</p>
                </div>
            </div>
            <div className="flex items-center justify-between gap-2 text-sm">
                <span className="min-w-0 break-all">{unit?.price ? money(unit.price) : 'Belum ada harga'}</span>
                <span className="text-muted-foreground max-w-[45%] text-right text-xs break-all">
                    {localDecimal(item.stock)} {item.base_unit_name}
                </span>
            </div>
            <div className="mt-auto flex items-center gap-2">
                <Select value={unit?.id} onValueChange={setUnitId}>
                    <SelectTrigger aria-label={`Satuan katalog ${item.name}`} className="min-w-0 flex-1">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {item.units.map((row) => (
                            <SelectItem key={row.id} value={row.id} disabled={row.price === null}>
                                {row.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button
                    size="icon"
                    variant="outline"
                    title={`Tambah ${item.name}`}
                    aria-label={`Tambah ${item.name}`}
                    disabled={!available || !unit}
                    onClick={() => onAdd(item, unit.id)}
                >
                    <PackagePlus className="text-emerald-600 dark:text-emerald-300" />
                </Button>
            </div>
        </article>
    );
}

export function ItemSearch({
    items,
    query,
    page,
    hasMore,
    loading,
    error,
    ready,
    onQuery,
    onPage,
    onAdd,
    onBarcode,
    onRetry,
    visual = false,
}: {
    items: PosItem[];
    query: string;
    page: number;
    hasMore: boolean;
    loading: boolean;
    error: string;
    ready: boolean;
    onQuery: (query: string) => void;
    onPage: (page: number) => void;
    onAdd: (item: PosItem, unitId: string) => void;
    onBarcode: () => void;
    onRetry: () => void;
    visual?: boolean;
}) {
    return (
        <section className="flex min-h-0 min-w-0 flex-1 flex-col" aria-label="Katalog barang" aria-busy={loading}>
            <div className={cn('shrink-0 space-y-2 border-b border-[var(--app-border)]', visual ? 'p-3' : 'p-4')}>
                <Label htmlFor="pos-search" className={visual ? 'sr-only' : ''}>
                    Cari barang
                </Label>
                <div className="relative">
                    <Search className="text-muted-foreground pointer-events-none absolute top-3 left-3 size-4" />
                    <Input
                        id="pos-search"
                        className="pl-9"
                        type="search"
                        placeholder="SKU, nama, atau barcode"
                        value={query}
                        disabled={!ready}
                        onChange={(event) => onQuery(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                onBarcode();
                            }
                        }}
                    />
                </div>
            </div>
            <div data-testid="catalog-scroll" className="min-h-0 flex-1 overflow-auto overscroll-contain p-3">
                {loading ? (
                    <div role="status" className="flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Memuat barang...
                    </div>
                ) : error ? (
                    <div className="space-y-3">
                        <p role="alert" className="text-destructive text-sm">
                            {error}
                        </p>
                        <Button variant="outline" onClick={onRetry}>
                            Coba lagi
                        </Button>
                    </div>
                ) : items.length ? (
                    <div className={cn('grid gap-3', visual ? 'grid-cols-[repeat(auto-fill,minmax(9.5rem,1fr))]' : 'xl:grid-cols-2')}>
                        {items.map((item) => (
                            <CatalogItem key={item.id} item={item} onAdd={onAdd} visual={visual} />
                        ))}
                    </div>
                ) : (
                    <p className="text-muted-foreground py-8 text-center text-sm">
                        {ready ? 'Tidak ada barang yang sesuai.' : 'Gudang dan pelanggan belum dipilih.'}
                    </p>
                )}
            </div>
            <div className="flex shrink-0 items-center justify-between border-t border-[var(--app-border)] px-4 py-2 text-sm">
                <span>Halaman {page}</span>
                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="Katalog sebelumnya"
                        disabled={page === 1 || loading || !ready}
                        onClick={() => onPage(page - 1)}
                    >
                        <ChevronLeft />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="Katalog berikutnya"
                        disabled={!hasMore || loading || !ready}
                        onClick={() => onPage(page + 1)}
                    >
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </section>
    );
}
