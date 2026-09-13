import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Minus, Plus, Trash2 } from 'lucide-react';
import { levelPrice, lineSubtotal, money, selectedUnit } from './pos-format';
import { type CartLine, type CustomerLevelOption } from '../types';

type CartPanelProps = {
    lines: CartLine[];
    customerLevel: CustomerLevelOption;
    onQtyChange: (itemId: string, qty: number) => void;
    onUnitChange: (itemId: string, unitCode: string) => void;
    onDiscountChange: (itemId: string, discount: number) => void;
    onRemove: (itemId: string) => void;
};

export function CartPanel({ lines, customerLevel, onQtyChange, onUnitChange, onDiscountChange, onRemove }: CartPanelProps) {
    return (
        <section className="flex min-h-0 flex-col rounded-lg border bg-background">
            <div className="flex items-center justify-between border-b p-4">
                <div>
                    <h2 className="text-base font-semibold">Keranjang</h2>
                    <p className="text-muted-foreground text-sm">{lines.length} item aktif</p>
                </div>
            </div>

            <div className="min-h-0 flex-1 overflow-auto">
                {lines.length === 0 ? (
                    <div className="text-muted-foreground flex h-full min-h-72 items-center justify-center p-6 text-center text-sm">
                        Keranjang masih kosong.
                    </div>
                ) : (
                    <div className="divide-y">
                        {lines.map((line) => {
                            const unit = selectedUnit(line);
                            const price = levelPrice(unit, customerLevel);

                            return (
                                <div key={line.item.id} className="grid gap-3 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold">{line.item.name}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {line.item.sku} / {money(price)}
                                            </p>
                                        </div>
                                        <Button variant="ghost" size="icon" aria-label={`Hapus ${line.item.name}`} onClick={() => onRemove(line.item.id)}>
                                            <Trash2 />
                                        </Button>
                                    </div>

                                    <div className="grid grid-cols-[minmax(0,1fr)_7rem_6rem] gap-2">
                                        <Select value={line.unitCode} onValueChange={(value) => onUnitChange(line.item.id, value)}>
                                            <SelectTrigger aria-label={`Satuan ${line.item.name}`}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {line.item.units.map((itemUnit) => (
                                                    <SelectItem key={itemUnit.code} value={itemUnit.code}>
                                                        {itemUnit.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <div className="grid grid-cols-[2rem_1fr_2rem] rounded-md border">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-10 w-8 rounded-r-none"
                                                aria-label={`Kurangi ${line.item.name}`}
                                                onClick={() => onQtyChange(line.item.id, line.qty - 1)}
                                            >
                                                <Minus />
                                            </Button>
                                            <Input
                                                id={`qty-${line.item.id}`}
                                                name={`qty_${line.item.id}`}
                                                aria-label={`Qty ${line.item.name}`}
                                                value={line.qty}
                                                onChange={(event) => onQtyChange(line.item.id, Number(event.target.value))}
                                                className="h-10 rounded-none border-0 px-1 text-center focus-visible:ring-0"
                                                inputMode="numeric"
                                            />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-10 w-8 rounded-l-none"
                                                aria-label={`Tambah ${line.item.name}`}
                                                onClick={() => onQtyChange(line.item.id, line.qty + 1)}
                                            >
                                                <Plus />
                                            </Button>
                                        </div>

                                        <Input
                                            id={`discount-${line.item.id}`}
                                            name={`discount_${line.item.id}`}
                                            aria-label={`Diskon ${line.item.name}`}
                                            value={line.discount}
                                            onChange={(event) => onDiscountChange(line.item.id, Number(event.target.value))}
                                            inputMode="numeric"
                                        />
                                    </div>

                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Subtotal</span>
                                        <span className="font-semibold">{money(lineSubtotal(line, customerLevel))}</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </section>
    );
}
