import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ShoppingBasket, Trash2 } from 'lucide-react';
import { money } from '../lib/decimal';
import type { CartLine, CartQuote } from '../types';

export function CartPanel({
    lines,
    quote,
    errors,
    onQuantity,
    onUnit,
    onRemove,
}: {
    lines: CartLine[];
    quote: CartQuote | null;
    errors: Record<string, string[]>;
    onQuantity: (index: number, quantity: string) => void;
    onUnit: (index: number, unitId: string) => void;
    onRemove: (index: number) => void;
}) {
    return (
        <section className="flex min-h-0 flex-1 flex-col" aria-label="Keranjang">
            <div className="flex items-center gap-3 border-b border-[var(--app-border)] p-4">
                <ShoppingBasket className="size-5 text-violet-600 dark:text-violet-300" />
                <h2 className="text-base font-semibold">Keranjang</h2>
                <span className="text-muted-foreground ml-auto text-sm">{lines.length} baris</span>
            </div>
            <div className="min-h-0 flex-1 overflow-auto">
                {lines.length === 0 ? (
                    <p className="text-muted-foreground py-10 text-center text-sm">Keranjang masih kosong.</p>
                ) : (
                    lines.map((line, index) => {
                        const checked = quote?.lines.find((row) => row.item_id === line.item.id && row.unit_id === line.unitId);
                        const error = errors[`lines.${index}.quantity`]?.[0] ?? errors[`lines.${index}.unit_id`]?.[0];
                        return (
                            <div key={line.item.id + ':' + index} className="space-y-3 border-b border-[var(--app-border)] p-4">
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <h3 className="truncate text-sm font-medium">{checked?.name ?? line.item.name}</h3>
                                        <p className="text-muted-foreground text-xs">{checked?.sku ?? line.item.sku}</p>
                                    </div>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label={`Hapus ${line.item.name}`}
                                        title="Hapus baris"
                                        onClick={() => onRemove(index)}
                                    >
                                        <Trash2 className="text-rose-600 dark:text-rose-300" />
                                    </Button>
                                </div>
                                <div className="grid grid-cols-[minmax(0,1fr)_8rem] gap-3">
                                    <Select value={line.unitId} onValueChange={(id) => onUnit(index, id)}>
                                        <SelectTrigger aria-label={`Satuan ${line.item.name}`}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {line.item.units.map((unit) => (
                                                <SelectItem key={unit.id} value={unit.id}>
                                                    {unit.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        aria-label={`Kuantitas ${line.item.name}`}
                                        value={line.quantity}
                                        inputMode="decimal"
                                        maxLength={27}
                                        aria-invalid={Boolean(error)}
                                        onChange={(event) => onQuantity(index, event.target.value)}
                                    />
                                </div>
                                {error && (
                                    <p role="alert" className="text-destructive text-sm">
                                        {error}
                                    </p>
                                )}
                                <div className="flex justify-between gap-3 text-sm">
                                    <span className="text-muted-foreground max-w-[48%] break-all">{checked ? money(checked.unit_price) : '-'}</span>
                                    <span className="max-w-[48%] text-right font-semibold break-all">{checked ? money(checked.subtotal) : '-'}</span>
                                </div>
                            </div>
                        );
                    })
                )}
            </div>
        </section>
    );
}
