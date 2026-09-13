import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PackagePlus, Search } from 'lucide-react';
import { money } from './pos-format';
import { type CustomerLevelOption, type PosItem } from '../types';

type ItemSearchProps = {
    items: PosItem[];
    query: string;
    category: string;
    customerLevel: CustomerLevelOption;
    onQueryChange: (value: string) => void;
    onCategoryChange: (value: string) => void;
    onAddItem: (item: PosItem) => void;
};

export function ItemSearch({ items, query, category, customerLevel, onQueryChange, onCategoryChange, onAddItem }: ItemSearchProps) {
    const categories = ['Semua', ...Array.from(new Set(items.map((item) => item.category)))];
    const normalizedQuery = query.trim().toLowerCase();
    const filteredItems = items.filter((item) => {
        const matchesCategory = category === 'Semua' || item.category === category;
        const matchesQuery = [item.name, item.sku, item.category].some((value) => value.toLowerCase().includes(normalizedQuery));

        return matchesCategory && matchesQuery;
    });

    return (
        <section className="flex min-h-0 flex-col rounded-lg border bg-background">
            <div className="border-b p-4">
                <div className="flex flex-col gap-3 xl:flex-row">
                    <div className="min-w-0 flex-1 space-y-1.5">
                        <Label htmlFor="item-search">Cari Item</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                            <Input
                                id="item-search"
                                value={query}
                                onChange={(event) => onQueryChange(event.target.value)}
                                className="pl-9"
                                placeholder="SKU, nama item, atau kategori"
                                type="search"
                            />
                        </div>
                    </div>
                    <div className="w-full space-y-1.5 xl:w-52">
                        <Label htmlFor="item-category">Kategori</Label>
                        <Select value={category} onValueChange={onCategoryChange}>
                            <SelectTrigger id="item-category">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((itemCategory) => (
                                    <SelectItem key={itemCategory} value={itemCategory}>
                                        {itemCategory}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <div className="min-h-0 flex-1 overflow-auto p-3">
                <div className="grid gap-3 xl:grid-cols-2">
                    {filteredItems.map((item) => {
                        const unit = item.units[0];
                        const price = Math.round(unit.price * customerLevel.priceModifier);

                        return (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => onAddItem(item)}
                                className="focus-visible:ring-ring flex min-h-32 rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent/60 focus-visible:outline-none focus-visible:ring-2"
                            >
                                <div className="flex min-w-0 flex-1 flex-col gap-2">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold">{item.name}</p>
                                            <p className="text-muted-foreground text-xs">{item.sku}</p>
                                        </div>
                                        <Badge variant="outline" className="shrink-0 rounded-md">
                                            {item.category}
                                        </Badge>
                                    </div>
                                    <div className="mt-auto grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <p className="text-muted-foreground text-xs">Harga {unit.label}</p>
                                            <p className="font-semibold">{money(price)}</p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">Stok</p>
                                            <p className="font-semibold">
                                                {item.stock} {item.baseUnit}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <PackagePlus className="text-muted-foreground ml-3 size-5 shrink-0" />
                            </button>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
